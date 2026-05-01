<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($username)) {
        $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Check if email already exists
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                // Dynamic column detection
                $cols = array_column($db->query('DESCRIBE users')->fetchAll(), 'Field');
                $pwCol = in_array('password_hash', $cols) ? 'password_hash' : 'password';
                
                // Build INSERT query dynamically
                $insertData = [
                    'email' => $email,
                    $pwCol => password_hash($password, PASSWORD_DEFAULT)
                ];
                
                if (in_array('name', $cols)) {
                    $insertData['name'] = $name;
                } elseif (in_array('first_name', $cols)) {
                    $nameParts = explode(' ', $name, 2);
                    $insertData['first_name'] = $nameParts[0];
                    if (isset($nameParts[1]) && in_array('last_name', $cols)) {
                        $insertData['last_name'] = $nameParts[1];
                    }
                }
                
                if (in_array('username', $cols)) {
                    $insertData['username'] = $username;
                }
                
                if (in_array('phone', $cols) && !empty($phone)) {
                    $insertData['phone'] = $phone;
                }
                
                if (in_array('role', $cols)) {
                    $insertData['role'] = 'user';
                }
                
                $columns = implode(', ', array_keys($insertData));
                $placeholders = ':' . implode(', :', array_keys($insertData));
                
                $stmt = $db->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
                $stmt->execute($insertData);
                
                $userId = $db->lastInsertId();
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                
                $success = 'Account created successfully! Redirecting to dashboard...';
                
                // Redirect after a short delay
                echo '<script>setTimeout(() => { window.location.href = "' . url('dashboard.php') . '"; }, 2000);</script>';
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - 10x AI Hustle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23334155" fill-opacity="0.05"%3E%3Ccircle cx="30" cy="30" r="1"%3E%3C/circle%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
    
    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <!-- Logo Section -->
            <div class="text-center mb-10">
                <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6 shadow-2xl">
                    <img src="https://10xaihustle.tsspages.com/media/images/media_69efc3f84c977_1777320952.png" alt="10x AI Hustle" class="h-16 mx-auto mb-4">
                    <h1 class="text-2xl font-bold text-white mb-2">Join 10x AI Hustle</h1>
                    <p class="text-slate-400">Start your journey to 10x productivity with AI</p>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-8 shadow-2xl">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-400 text-sm"><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-green-400 text-sm"><?= htmlspecialchars($success) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-300 mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your full name">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your email">
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                            <input type="text" id="username" name="username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                   placeholder="Choose a username (optional)">
                            <p class="text-xs text-slate-500 mt-1">Leave blank to auto-generate</p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-300 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your phone number (optional)">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password *</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                       class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                       placeholder="Create a password">
                                <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-300">
                                    <svg id="eye-closed-1" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                    </svg>
                                    <svg id="eye-open-1" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2">
                                <div class="flex text-xs text-slate-500 space-x-4">
                                    <span id="length-check" class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                        6+ characters
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-slate-300 mb-2">Confirm Password *</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600/50 text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                                       placeholder="Confirm your password">
                                <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-300">
                                    <svg id="eye-closed-2" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                    </svg>
                                    <svg id="eye-open-2" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <input type="checkbox" id="terms" required class="mt-1 rounded border-slate-600 bg-slate-700 text-blue-500 focus:ring-blue-500 focus:ring-offset-0">
                        <label for="terms" class="ml-3 text-sm text-slate-400">
                            I agree to the <a href="#" class="text-blue-400 hover:text-blue-300">Terms of Service</a> and <a href="#" class="text-blue-400 hover:text-blue-300">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                            Create Account
                        </div>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-700/50">
                    <p class="text-center text-slate-400">
                        Already have an account?
                        <a href="<?= url('login.php') ?>" class="text-blue-400 hover:text-blue-300 font-medium transition-colors">Sign in here</a>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-6">
                <a href="<?= url('index.php') ?>" class="inline-flex items-center text-slate-400 hover:text-slate-300 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeClosed = document.getElementById('eye-closed-' + (fieldId === 'password' ? '1' : '2'));
            const eyeOpen = document.getElementById('eye-open-' + (fieldId === 'password' ? '1' : '2'));
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeClosed.classList.add('hidden');
                eyeOpen.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeClosed.classList.remove('hidden');
                eyeOpen.classList.add('hidden');
            }
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const lengthCheck = document.getElementById('length-check');
            
            if (password.length >= 6) {
                lengthCheck.classList.remove('text-slate-500');
                lengthCheck.classList.add('text-green-400');
                lengthCheck.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
            } else {
                lengthCheck.classList.remove('text-green-400');
                lengthCheck.classList.add('text-slate-500');
                lengthCheck.querySelector('svg').innerHTML = '<circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"/>';
            }
        });
    </script>
</body>
</html>