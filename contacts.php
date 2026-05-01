<?php
require_once 'config.php';
require_once 'includes/functions.php';
requireAuth();

$user = getCurrentUser();
$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: ' . url('contacts.php'));
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $status = $_POST['status'] ?? 'lead';
        $tags = trim($_POST['tags'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($first_name)) {
            setFlash('error', 'First name is required.');
        } else {
            $stmt = $db->prepare('INSERT INTO contacts (user_id, first_name, last_name, email, phone, company, status, tags, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], $first_name, $last_name, $email, $phone, $company, $status, $tags, $notes]);
            setFlash('success', 'Contact added successfully!');
        }
        
        header('Location: ' . url('contacts.php'));
        exit;
    }
    
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $status = $_POST['status'] ?? 'lead';
        $tags = trim($_POST['tags'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($first_name)) {
            setFlash('error', 'First name is required.');
        } else {
            $stmt = $db->prepare('UPDATE contacts SET first_name=?, last_name=?, email=?, phone=?, company=?, status=?, tags=?, notes=? WHERE id=? AND user_id=?');
            $stmt->execute([$first_name, $last_name, $email, $phone, $company, $status, $tags, $notes, $id, $user['id']]);
            setFlash('success', 'Contact updated successfully!');
        }
        
        header('Location: ' . url('contacts.php'));
        exit;
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM contacts WHERE id=? AND user_id=?');
        $stmt->execute([$id, $user['id']]);
        setFlash('success', 'Contact deleted successfully!');
        
        header('Location: ' . url('contacts.php'));
        exit;
    }
    
    if ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $csvFile = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($csvFile);
            $imported = 0;
            
            while (($row = fgetcsv($csvFile)) !== FALSE) {
                if (count($row) >= 2) {
                    $first_name = $row[0] ?? '';
                    $last_name = $row[1] ?? '';
                    $email = $row[2] ?? '';
                    $phone = $row[3] ?? '';
                    $company = $row[4] ?? '';
                    
                    if (!empty($first_name)) {
                        $stmt = $db->prepare('INSERT INTO contacts (user_id, first_name, last_name, email, phone, company, status) VALUES (?, ?, ?, ?, ?, ?, "lead")');
                        $stmt->execute([$user['id'], $first_name, $last_name, $email, $phone, $company]);
                        $imported++;
                    }
                }
            }
            fclose($csvFile);
            
            setFlash('success', "Successfully imported {$imported} contacts!");
        } else {
            setFlash('error', 'Please select a valid CSV file.');
        }
        
        header('Location: ' . url('contacts.php'));
        exit;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$tag_filter = $_GET['tag'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build query with filters
$whereConditions = ['user_id = ?'];
$params = [$user['id']];

if (!empty($search)) {
    $whereConditions[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($status_filter)) {
    $whereConditions[] = 'status = ?';
    $params[] = $status_filter;
}

if (!empty($tag_filter)) {
    $whereConditions[] = 'tags LIKE ?';
    $params[] = '%' . $tag_filter . '%';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get total count for pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM contacts $whereClause");
$countStmt->execute($params);
$totalContacts = $countStmt->fetchColumn();
$totalPages = ceil($totalContacts / ITEMS_PER_PAGE);

// Get contacts
$stmt = $db->prepare("SELECT * FROM contacts $whereClause ORDER BY created_at DESC LIMIT " . (int)ITEMS_PER_PAGE . " OFFSET " . (int)$offset);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// Get unique statuses and tags for filters
$statusStmt = $db->prepare('SELECT DISTINCT status FROM contacts WHERE user_id = ? AND status IS NOT NULL');
$statusStmt->execute([$user['id']]);
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

$tagStmt = $db->prepare('SELECT DISTINCT tags FROM contacts WHERE user_id = ? AND tags IS NOT NULL AND tags != ""');
$tagStmt->execute([$user['id']]);
$allTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
$uniqueTags = [];
foreach ($allTags as $tagString) {
    $tags = array_map('trim', explode(',', $tagString));
    $uniqueTags = array_merge($uniqueTags, $tags);
}
$uniqueTags = array_unique(array_filter($uniqueTags));

// Get contact for editing if edit mode
$editContact = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStmt = $db->prepare('SELECT * FROM contacts WHERE id = ? AND user_id = ?');
    $editStmt->execute([$editId, $user['id']]);
    $editContact = $editStmt->fetch();
}

function getStatusBadge($status) {
    $colors = [
        'lead' => 'bg-blue-500/20 text-blue-400',
        'customer' => 'bg-green-500/20 text-green-400',
        'inactive' => 'bg-slate-800/300/20 text-gray-400',
        'prospect' => 'bg-purple-500/20 text-purple-400'
    ];
    $color = $colors[$status] ?? 'bg-slate-800/300/20 text-gray-400';
    return "<span class='px-2 py-1 text-xs font-medium rounded-full {$color}'>" . ucfirst($status) . '</span>';
}

function formatTags($tags) {
    if (empty($tags)) return '';
    $tagArray = array_map('trim', explode(',', $tags));
    $html = '';
    foreach ($tagArray as $tag) {
        if (!empty($tag)) {
            $html .= "<span class='px-2 py-1 text-xs font-medium rounded bg-indigo-500/20 text-indigo-400 mr-1'>{$tag}</span>";
        }
    }
    return $html;
}

require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-extrabold mb-2">Contact Management</h1>
                <p class="text-indigo-200">Manage your leads, prospects, and customers in one place</p>
            </div>
            <div class="bg-slate-800/50/10 backdrop-blur px-4 py-2 rounded-xl">
                <div class="text-2xl font-bold"><?= number_format($totalContacts) ?></div>
                <div class="text-sm opacity-80">Total Contacts</div>
            </div>
        </div>
    </div>

    <?= getFlash() ?>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <button onclick="toggleModal('addModal')" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105 shadow-lg">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Contact
        </button>
        <button onclick="toggleModal('importModal')" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Import CSV
        </button>
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4">
            <div class="text-2xl font-bold text-white"><?= count(array_filter($contacts, fn($c) => $c['status'] === 'lead')) ?></div>
            <div class="text-slate-400 text-sm">New Leads</div>
        </div>
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4">
            <div class="text-2xl font-bold text-green-400"><?= count(array_filter($contacts, fn($c) => $c['status'] === 'customer')) ?></div>
            <div class="text-slate-400 text-sm">Customers</div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search contacts..." class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <select name="status" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= $status_filter === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select name="tag" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">All Tags</option>
                    <?php foreach ($uniqueTags as $tag): ?>
                        <option value="<?= e($tag) ?>" <?= $tag_filter === $tag ? 'selected' : '' ?>><?= e($tag) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                    Filter
                </button>
                <a href="<?= url('contacts.php') ?>" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Contacts Table -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Tags</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <h3 class="text-lg font-medium mb-2">No contacts found</h3>
                                <p class="mb-4">Start building your network by adding your first contact.</p>
                                <button onclick="toggleModal('addModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-all duration-300">
                                    Add First Contact
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="text-white font-semibold"><?= e($contact['first_name'] . ' ' . $contact['last_name']) ?></div>
                                    <?php if (!empty($contact['email'])): ?>
                                        <div class="text-slate-400 text-sm"><?= e($contact['email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['phone'])): ?>
                                        <div class="text-slate-400 text-sm"><?= e($contact['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-300"><?= e($contact['company'] ?: 'N/A') ?></td>
                                <td class="px-6 py-4"><?= getStatusBadge($contact['status']) ?></td>
                                <td class="px-6 py-4"><?= formatTags($contact['tags']) ?></td>
                                <td class="px-6 py-4 text-slate-400 text-sm"><?= date('M j, Y', strtotime($contact['created_at'])) ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="<?= url('contacts.php?edit=' . $contact['id']) ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-xs font-semibold transition-all duration-300">
                                            Edit
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs font-semibold transition-all duration-300">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-slate-700/30 px-6 py-4 border-t border-slate-700/50">
                <div class="flex justify-between items-center">
                    <div class="text-slate-400 text-sm">
                        Showing <?= $offset + 1 ?> to <?= min($offset + ITEMS_PER_PAGE, $totalContacts) ?> of <?= $totalContacts ?> contacts
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="<?= url('contacts.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="bg-slate-600 hover:bg-slate-500 text-white px-3 py-2 rounded-lg text-sm transition-all duration-300">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="<?= url('contacts.php?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>" 
                               class="<?= $i === $page ? 'bg-indigo-600' : 'bg-slate-600 hover:bg-slate-500' ?> text-white px-3 py-2 rounded-lg text-sm transition-all duration-300">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= url('contacts.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="bg-slate-600 hover:bg-slate-500 text-white px-3 py-2 rounded-lg text-sm transition-all duration-300">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Contact Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-extrabold text-white">Add New Contact</h2>
                <button onclick="toggleModal('addModal')" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">First Name *</label>
                        <input type="text" name="first_name" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Last Name</label>
                        <input type="text" name="last_name" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                    <input type="email" name="email" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                    <input type="tel" name="phone" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Company</label>
                    <input type="text" name="company" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                    <select name="status" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="lead">Lead</option>
                        <option value="prospect">Prospect</option>
                        <option value="customer">Customer</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Tags (comma-separated)</label>
                    <input type="text" name="tags" placeholder="e.g. hot-lead, enterprise, referral" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('addModal')" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Cancel
                    </button>
                    <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Add Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="importModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-extrabold text-white">Import Contacts from CSV</h2>
                <button onclick="toggleModal('importModal')" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="import_csv">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                </div>
                
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-slate-300 mb-2">CSV Format Expected:</h3>
                    <div class="bg-slate-700/50 rounded-xl p-3 text-sm text-slate-400">
                        <div class="font-mono">FirstName,LastName,Email,Phone,Company</div>
                        <div class="font-mono text-xs mt-1 opacity-75">John,Doe,john@example.com,555-1234,Acme Corp</div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('importModal')" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Import Contacts
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<?php if ($editContact): ?>
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-extrabold text-white">Edit Contact</h2>
                <a href="<?= url('contacts.php') ?>" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $editContact['id'] ?>">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">First Name *</label>
                        <input type="text" name="first_name" value="<?= e($editContact['first_name']) ?>" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Last Name</label>
                        <input type="text" name="last_name" value="<?= e($editContact['last_name']) ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                    <input type="email" name="email" value="<?= e($editContact['email']) ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                    <input type="tel" name="phone" value="<?= e($editContact['phone']) ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Company</label>
                    <input type="text" name="company" value="<?= e($editContact['company']) ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                    <select name="status" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="lead" <?= $editContact['status'] === 'lead' ? 'selected' : '' ?>>Lead</option>
                        <option value="prospect" <?= $editContact['status'] === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                        <option value="customer" <?= $editContact['status'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="inactive" <?= $editContact['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Tags (comma-separated)</label>
                    <input type="text" name="tags" value="<?= e($editContact['tags']) ?>" placeholder="e.g. hot-lead, enterprise, referral" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?= e($editContact['notes']) ?></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <a href="<?= url('contacts.php') ?>" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Cancel
                    </a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                        Update Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.toggle('hidden');
}

// Close modals when clicking outside
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});

// Auto-show edit modal if edit parameter exists
<?php if ($editContact): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Edit modal is already shown via PHP
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>