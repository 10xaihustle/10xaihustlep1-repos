<?php
require_once 'config.php';
require_once 'includes/functions.php';

requireAuth();
$user = getCurrentUser();

// Check admin role
if ($user['role'] !== 'admin') {
    header('Location: ' . url('index.php'));
    exit;
}

$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid CSRF token');
        header('Location: ' . url('admin.php'));
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_user_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? 'user';
        
        if ($userId > 0 && in_array($newRole, ['admin', 'user'])) {
            $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$newRole, $userId]);
            setFlash('success', 'User role updated successfully');
        }
        
        header('Location: ' . url('admin.php'));
        exit;
    }
    
    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId > 0 && $userId !== $user['id']) {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            setFlash('success', 'User deleted successfully');
        } else {
            setFlash('error', 'Cannot delete your own account');
        }
        
        header('Location: ' . url('admin.php'));
        exit;
    }
    
    if ($action === 'update_setting') {
        $key = $_POST['setting_key'] ?? '';
        $value = $_POST['setting_value'] ?? '';
        
        if ($key) {
            $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute([$key, $value]);
            setFlash('success', 'Setting updated successfully');
        }
        
        header('Location: ' . url('admin.php'));
        exit;
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Get users with search
$searchCondition = '';
$searchParams = [];
if ($search) {
    $searchCondition = 'WHERE name LIKE ? OR email LIKE ? OR username LIKE ?';
    $searchTerm = '%' . $search . '%';
    $searchParams = [$searchTerm, $searchTerm, $searchTerm];
}

$stmt = $db->prepare("SELECT * FROM users {$searchCondition} ORDER BY created_at DESC LIMIT " . (int)ITEMS_PER_PAGE . " OFFSET " . (int)$offset);
$stmt->execute($searchParams);
$users = $stmt->fetchAll();

// Get total users count
$countStmt = $db->prepare("SELECT COUNT(*) FROM users {$searchCondition}");
$countStmt->execute($searchParams);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / ITEMS_PER_PAGE);

// Get system statistics
$stats = [];
$stats['total_users'] = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['total_contacts'] = $db->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
$stats['total_ai_tools'] = $db->query('SELECT COUNT(*) FROM ai_tools')->fetchColumn();
$stats['total_sessions'] = $db->query('SELECT COUNT(*) FROM productivity_sessions')->fetchColumn();
$stats['total_achievements'] = $db->query('SELECT COUNT(*) FROM achievements')->fetchColumn();

// Get user growth data (last 30 days)
$userGrowthStmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30
");
$userGrowth = $userGrowthStmt->fetchAll();

// Get activity data (productivity sessions)
$activityStmt = $db->query("
    SELECT DATE(session_date) as date, COUNT(*) as sessions, AVG(productivity_score) as avg_score
    FROM productivity_sessions 
    WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(session_date)
    ORDER BY date DESC
    LIMIT 30
");
$activityData = $activityStmt->fetchAll();

// Get settings
$settingsStmt = $db->query('SELECT * FROM settings ORDER BY setting_key');
$settings = $settingsStmt->fetchAll();

// Get recent activity
$recentActivityStmt = $db->query("
    SELECT 'user_registration' as type, name as title, created_at, 'New user registered' as description
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'productivity_session' as type, session_name as title, created_at, CONCAT('Session completed with score: ', productivity_score) as description
    FROM productivity_sessions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'ai_tool_added' as type, tool_name as title, created_at, 'New AI tool added' as description
    FROM ai_tools
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
    LIMIT 10
");
$recentActivity = $recentActivityStmt->fetchAll();

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="border-b border-slate-700/50 pb-6">
        <h1 class="text-4xl font-extrabold text-white mb-3">Admin Panel</h1>
        <p class="text-xl text-slate-400">Manage users, monitor system activity, and configure settings</p>
    </div>

    <?php if ($flash = getFlash()): ?>
        <div class="rounded-xl p-4 <?= $flash['type'] === 'error' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- System Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Total Users</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($stats['total_users']) ?></p>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Contacts</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($stats['total_contacts']) ?></p>
                </div>
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">AI Tools</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($stats['total_ai_tools']) ?></p>
                </div>
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Sessions</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($stats['total_sessions']) ?></p>
                </div>
                <div class="bg-gradient-to-r from-rose-500 to-pink-600 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Achievements</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($stats['total_achievements']) ?></p>
                </div>
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl">
            <h3 class="text-xl font-bold text-white mb-6">User Registration Growth</h3>
            <canvas id="userGrowthChart" height="300"></canvas>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl">
            <h3 class="text-xl font-bold text-white mb-6">Productivity Activity</h3>
            <canvas id="activityChart" height="300"></canvas>
        </div>
    </div>

    <!-- User Management -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-white">User Management</h3>
            <form method="GET" class="flex gap-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search users..." class="bg-slate-900/50 border border-slate-600 text-white rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-2 rounded-xl font-medium transition-all duration-300 hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </form>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-left py-4 px-6 text-slate-300 font-medium">User</th>
                        <th class="text-left py-4 px-6 text-slate-300 font-medium">Email</th>
                        <th class="text-left py-4 px-6 text-slate-300 font-medium">Role</th>
                        <th class="text-left py-4 px-6 text-slate-300 font-medium">Created</th>
                        <th class="text-left py-4 px-6 text-slate-300 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $userData): ?>
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                        <?= strtoupper(substr($userData['name'] ?? $userData['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium"><?= e($userData['name'] ?? $userData['username']) ?></p>
                                        <p class="text-slate-400 text-sm">@<?= e($userData['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-300"><?= e($userData['email']) ?></td>
                            <td class="py-4 px-6">
                                <form method="POST" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_user_role">
                                    <input type="hidden" name="user_id" value="<?= $userData['id'] ?>">
                                    <select name="role" onchange="this.form.submit()" class="bg-slate-900/50 border border-slate-600 text-white rounded-lg px-3 py-1 text-sm">
                                        <option value="user" <?= $userData['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td class="py-4 px-6 text-slate-400"><?= timeAgo($userData['created_at']) ?></td>
                            <td class="py-4 px-6">
                                <?php if ($userData['id'] !== $user['id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $userData['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center gap-2 mt-6">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= url('admin.php?page=' . $i . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' ?> transition-colors">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- System Settings -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl">
        <h3 class="text-2xl font-bold text-white mb-6">System Settings</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($settings as $setting): ?>
                <form method="POST" class="bg-slate-900/50 border border-slate-600 rounded-xl p-6">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_setting">
                    <input type="hidden" name="setting_key" value="<?= e($setting['setting_key']) ?>">
                    
                    <label class="block text-sm font-medium text-slate-300 mb-2"><?= e($setting['setting_key']) ?></label>
                    <div class="flex gap-3">
                        <input type="text" name="setting_value" value="<?= e($setting['setting_value'] ?? '') ?>" 
                               class="flex-1 bg-slate-800/50 border border-slate-600 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-300">
                            Save
                        </button>
                    </div>
                </form>
            <?php endforeach; ?>
            
            <!-- Add new setting -->
            <form method="POST" class="bg-slate-900/50 border border-slate-600 rounded-xl p-6">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_setting">
                
                <label class="block text-sm font-medium text-slate-300 mb-2">Add New Setting</label>
                <div class="space-y-3">
                    <input type="text" name="setting_key" placeholder="Setting key" required
                           class="w-full bg-slate-800/50 border border-slate-600 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <input type="text" name="setting_value" placeholder="Setting value" 
                           class="w-full bg-slate-800/50 border border-slate-600 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white py-2 rounded-lg font-medium transition-all duration-300">
                        Add Setting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl">
        <h3 class="text-2xl font-bold text-white mb-6">Recent Activity</h3>
        
        <div class="space-y-4">
            <?php foreach ($recentActivity as $activity): ?>
                <div class="flex items-center gap-4 p-4 bg-slate-900/50 rounded-xl border border-slate-700/50">
                    <div class="w-2 h-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full"></div>
                    <div class="flex-1">
                        <p class="text-white font-medium"><?= e($activity['title']) ?></p>
                        <p class="text-slate-400 text-sm"><?= e($activity['description']) ?></p>
                    </div>
                    <span class="text-slate-500 text-sm"><?= timeAgo($activity['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// User Growth Chart
const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
const userGrowthData = <?= json_encode(array_reverse($userGrowth)) ?>;

new Chart(userGrowthCtx, {
    type: 'line',
    data: {
        labels: userGrowthData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'New Users',
            data: userGrowthData.map(item => item.count),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: 'rgb(148, 163, 184)'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: 'rgb(148, 163, 184)'
                },
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                }
            },
            y: {
                ticks: {
                    color: 'rgb(148, 163, 184)'
                },
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                }
            }
        }
    }
});

// Activity Chart
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityChartData = <?= json_encode(array_reverse($activityData)) ?>;

new Chart(activityCtx, {
    type: 'bar',
    data: {
        labels: activityChartData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Productivity Sessions',
            data: activityChartData.map(item => item.sessions),
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: 'rgb(148, 163, 184)'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: 'rgb(148, 163, 184)'
                },
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                }
            },
            y: {
                ticks: {
                    color: 'rgb(148, 163, 184)'
                },
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>