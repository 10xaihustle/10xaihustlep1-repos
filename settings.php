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
        header('Location: ' . url('settings.php'));
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            setFlash('error', 'Name and email are required.');
        } else {
            // Check if email is already taken by another user
            $emailStmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $emailStmt->execute([$email, $user['id']]);
            
            if ($emailStmt->fetch()) {
                setFlash('error', 'Email address is already taken.');
            } else {
                // Detect columns dynamically
                $cols = array_column($db->query('DESCRIBE users')->fetchAll(), 'Field');
                $nameCol = in_array('first_name', $cols) ? 'first_name' : (in_array('name', $cols) ? 'name' : null);
                
                if ($nameCol) {
                    $updateFields = ["$nameCol = ?", 'email = ?'];
                    $updateParams = [$name, $email];
                    
                    if (in_array('phone', $cols)) {
                        $updateFields[] = 'phone = ?';
                        $updateParams[] = $phone;
                    }
                    
                    $updateParams[] = $user['id'];
                    
                    $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?');
                    $stmt->execute($updateParams);
                    
                    setFlash('success', 'Profile updated successfully!');
                } else {
                    setFlash('error', 'Unable to update profile. Contact administrator.');
                }
            }
        }
        
        header('Location: ' . url('settings.php'));
        exit;
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlash('error', 'All password fields are required.');
        } elseif ($new_password !== $confirm_password) {
            setFlash('error', 'New passwords do not match.');
        } elseif (strlen($new_password) < 6) {
            setFlash('error', 'New password must be at least 6 characters long.');
        } else {
            // Verify current password
            $cols = array_column($db->query('DESCRIBE users')->fetchAll(), 'Field');
            $pwCol = in_array('password_hash', $cols) ? 'password_hash' : 'password';
            
            if (!password_verify($current_password, $user[$pwCol])) {
                setFlash('error', 'Current password is incorrect.');
            } else {
                $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET $pwCol = ? WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);
                
                setFlash('success', 'Password changed successfully!');
            }
        }
        
        header('Location: ' . url('settings.php'));
        exit;
    }
    
    if ($action === 'update_preferences') {
        $preferences = [
            'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
            'push_notifications' => isset($_POST['push_notifications']) ? '1' : '0',
            'daily_digest' => isset($_POST['daily_digest']) ? '1' : '0',
            'date_format' => $_POST['date_format'] ?? 'M j, Y',
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'items_per_page' => (int)($_POST['items_per_page'] ?? 25)
        ];
        
        foreach ($preferences as $key => $value) {
            $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute(["user_{$user['id']}_$key", $value]);
        }
        
        setFlash('success', 'Preferences updated successfully!');
        header('Location: ' . url('settings.php'));
        exit;
    }
    
    if ($action === 'update_app_settings' && $user['role'] === 'admin') {
        $appSettings = [
            'app_name' => trim($_POST['app_name'] ?? ''),
            'app_description' => trim($_POST['app_description'] ?? ''),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
            'max_users' => (int)($_POST['max_users'] ?? 1000)
        ];
        
        foreach ($appSettings as $key => $value) {
            $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute([$key, $value]);
        }
        
        setFlash('success', 'App settings updated successfully!');
        header('Location: ' . url('settings.php'));
        exit;
    }
}

// Get user preferences
$preferencesStmt = $db->prepare('SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ?');
$preferencesStmt->execute(["user_{$user['id']}_%"]);
$preferencesData = $preferencesStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$preferences = [
    'email_notifications' => $preferencesData["user_{$user['id']}_email_notifications"] ?? '1',
    'push_notifications' => $preferencesData["user_{$user['id']}_push_notifications"] ?? '1',
    'daily_digest' => $preferencesData["user_{$user['id']}_daily_digest"] ?? '0',
    'date_format' => $preferencesData["user_{$user['id']}_date_format"] ?? 'M j, Y',
    'timezone' => $preferencesData["user_{$user['id']}_timezone"] ?? 'UTC',
    'items_per_page' => (int)($preferencesData["user_{$user['id']}_items_per_page"] ?? 25)
];

// Get app settings if admin
$appSettings = [];
if ($user['role'] === 'admin') {
    $appSettingsStmt = $db->prepare('SELECT setting_key, setting_value FROM settings WHERE setting_key IN ("app_name", "app_description", "maintenance_mode", "registration_enabled", "max_users")');
    $appSettingsStmt->execute();
    $appSettings = $appSettingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $appSettings = [
        'app_name' => $appSettings['app_name'] ?? '10x AI Hustle',
        'app_description' => $appSettings['app_description'] ?? 'AI-powered productivity platform',
        'maintenance_mode' => $appSettings['maintenance_mode'] ?? '0',
        'registration_enabled' => $appSettings['registration_enabled'] ?? '1',
        'max_users' => (int)($appSettings['max_users'] ?? 1000)
    ];
}

// Get user statistics
$statsStmt = $db->prepare('
    SELECT 
        (SELECT COUNT(*) FROM contacts WHERE user_id = ?) as total_contacts,
        (SELECT COUNT(*) FROM ai_tools WHERE user_id = ?) as total_ai_tools,
        (SELECT COUNT(*) FROM productivity_sessions WHERE user_id = ?) as total_sessions,
        (SELECT SUM(points) FROM achievements WHERE user_id = ?) as total_points
');
$statsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$userStats = $statsStmt->fetch();

require_once 'includes/header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-extrabold mb-2">Account Settings</h1>
                <p class="text-purple-200">Manage your profile, preferences, and account security</p>
            </div>
            <div class="bg-slate-800/50/10 backdrop-blur px-4 py-2 rounded-xl">
                <div class="text-2xl font-bold"><?= number_format($userStats['total_points'] ?? 0) ?></div>
                <div class="text-sm opacity-80">Total Points</div>
            </div>
        </div>
    </div>

    <?= getFlash() ?>

    <!-- User Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Contacts</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($userStats['total_contacts'] ?? 0) ?></p>
                </div>
                <div class="bg-blue-500/20 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">AI Tools</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($userStats['total_ai_tools'] ?? 0) ?></p>
                </div>
                <div class="bg-green-500/20 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Sessions</p>
                    <p class="text-2xl font-bold text-white"><?= number_format($userStats['total_sessions'] ?? 0) ?></p>
                </div>
                <div class="bg-purple-500/20 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Account Type</p>
                    <p class="text-lg font-bold text-white"><?= ucfirst($user['role']) ?></p>
                </div>
                <div class="bg-indigo-500/20 p-3 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Settings -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center mb-6">
                <div class="bg-indigo-500/20 p-3 rounded-xl mr-4">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Profile Information</h2>
            </div>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                    <input type="text" name="name" value="<?= e($user['name'] ?? $user['first_name'] ?? '') ?>" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Phone Number</label>
                    <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                    Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center mb-6">
                <div class="bg-red-500/20 p-3 rounded-xl mr-4">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Change Password</h2>
            </div>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                    Change Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Preferences -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
        <div class="flex items-center mb-6">
            <div class="bg-green-500/20 p-3 rounded-xl mr-4">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white">Preferences</h2>
        </div>
        
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_preferences">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Notifications</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-slate-300">Email Notifications</label>
                            <div class="relative">
                                <input type="checkbox" name="email_notifications" <?= $preferences['email_notifications'] === '1' ? 'checked' : '' ?> class="sr-only" onchange="toggleSwitch(this)">
                                <div class="toggle-switch w-12 h-6 bg-slate-600 rounded-full cursor-pointer transition-colors duration-300" onclick="toggleSwitchClick(this)">
                                    <div class="toggle-dot w-5 h-5 bg-slate-800/50 rounded-full shadow-md transform transition-transform duration-300"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <label class="text-slate-300">Push Notifications</label>
                            <div class="relative">
                                <input type="checkbox" name="push_notifications" <?= $preferences['push_notifications'] === '1' ? 'checked' : '' ?> class="sr-only" onchange="toggleSwitch(this)">
                                <div class="toggle-switch w-12 h-6 bg-slate-600 rounded-full cursor-pointer transition-colors duration-300" onclick="toggleSwitchClick(this)">
                                    <div class="toggle-dot w-5 h-5 bg-slate-800/50 rounded-full shadow-md transform transition-transform duration-300"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <label class="text-slate-300">Daily Digest</label>
                            <div class="relative">
                                <input type="checkbox" name="daily_digest" <?= $preferences['daily_digest'] === '1' ? 'checked' : '' ?> class="sr-only" onchange="toggleSwitch(this)">
                                <div class="toggle-switch w-12 h-6 bg-slate-600 rounded-full cursor-pointer transition-colors duration-300" onclick="toggleSwitchClick(this)">
                                    <div class="toggle-dot w-5 h-5 bg-slate-800/50 rounded-full shadow-md transform transition-transform duration-300"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Display Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Date Format</label>
                            <select name="date_format" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="M j, Y" <?= $preferences['date_format'] === 'M j, Y' ? 'selected' : '' ?>>Jan 15, 2024</option>
                                <option value="Y-m-d" <?= $preferences['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>2024-01-15</option>
                                <option value="d/m/Y" <?= $preferences['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>15/01/2024</option>
                                <option value="m/d/Y" <?= $preferences['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>01/15/2024</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Timezone</label>
                            <select name="timezone" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="UTC" <?= $preferences['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= $preferences['timezone'] === 'America/New_York' ? 'selected' : '' ?>>Eastern Time</option>
                                <option value="America/Chicago" <?= $preferences['timezone'] === 'America/Chicago' ? 'selected' : '' ?>>Central Time</option>
                                <option value="America/Denver" <?= $preferences['timezone'] === 'America/Denver' ? 'selected' : '' ?>>Mountain Time</option>
                                <option value="America/Los_Angeles" <?= $preferences['timezone'] === 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time</option>
                                <option value="Europe/London" <?= $preferences['timezone'] === 'Europe/London' ? 'selected' : '' ?>>London</option>
                                <option value="Europe/Paris" <?= $preferences['timezone'] === 'Europe/Paris' ? 'selected' : '' ?>>Paris</option>
                                <option value="Asia/Tokyo" <?= $preferences['timezone'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Tokyo</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Items Per Page</label>
                            <select name="items_per_page" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="10" <?= $preferences['items_per_page'] === 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $preferences['items_per_page'] === 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $preferences['items_per_page'] === 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $preferences['items_per_page'] === 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
    
    <?php if ($user['role'] === 'admin'): ?>
    <!-- Admin Settings -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6">
        <div class="flex items-center mb-6">
            <div class="bg-yellow-500/20 p-3 rounded-xl mr-4">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white">Admin Settings</h2>
        </div>
        
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_app_settings">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Application Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">App Name</label>
                            <input type="text" name="app_name" value="<?= e($appSettings['app_name']) ?>" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">App Description</label>
                            <textarea name="app_description" rows="3" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"><?= e($appSettings['app_description']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Max Users</label>
                            <input type="number" name="max_users" value="<?= $appSettings['max_users'] ?>" min="1" class="w-full bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">System Controls</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-slate-300 font-medium">Maintenance Mode</label>
                                <p class="text-slate-500 text-sm">Disable access for non-admin users</p>
                            </div>
                            <div class="relative">
                                <input type="checkbox" name="maintenance_mode" <?= $appSettings['maintenance_mode'] === '1' ? 'checked' : '' ?> class="sr-only" onchange="toggleSwitch(this)">
                                <div class="toggle-switch w-12 h-6 bg-slate-600 rounded-full cursor-pointer transition-colors duration-300" onclick="toggleSwitchClick(this)">
                                    <div class="toggle-dot w-5 h-5 bg-slate-800/50 rounded-full shadow-md transform transition-transform duration-300"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-slate-300 font-medium">Registration Enabled</label>
                                <p class="text-slate-500 text-sm">Allow new users to register</p>
                            </div>
                            <div class="relative">
                                <input type="checkbox" name="registration_enabled" <?= $appSettings['registration_enabled'] === '1' ? 'checked' : '' ?> class="sr-only" onchange="toggleSwitch(this)">
                                <div class="toggle-switch w-12 h-6 bg-slate-600 rounded-full cursor-pointer transition-colors duration-300" onclick="toggleSwitchClick(this)">
                                    <div class="toggle-dot w-5 h-5 bg-slate-800/50 rounded-full shadow-md transform transition-transform duration-300"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300">
                    Save Admin Settings
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.toggle-switch.active {
    background-color: #10b981;
}

.toggle-switch.active .toggle-dot {
    transform: translateX(24px);
}
</style>

<script>
function initializeToggles() {
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        const toggleSwitch = checkbox.nextElementSibling;
        if (toggleSwitch && toggleSwitch.classList.contains('toggle-switch')) {
            if (checkbox.checked) {
                toggleSwitch.classList.add('active');
            }
        }
    });
}

function toggleSwitch(checkbox) {
    const toggleSwitch = checkbox.nextElementSibling;
    if (checkbox.checked) {
        toggleSwitch.classList.add('active');
    } else {
        toggleSwitch.classList.remove('active');
    }
}

function toggleSwitchClick(toggleElement) {
    const checkbox = toggleElement.previousElementSibling;
    checkbox.checked = !checkbox.checked;
    toggleSwitch(checkbox);
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    initializeToggles();
    
    const newPasswordInput = document.querySelector('input[name="new_password"]');
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    
    if (newPasswordInput && confirmPasswordInput) {
        function validatePasswordMatch() {
            if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        }
        
        newPasswordInput.addEventListener('input', validatePasswordMatch);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>