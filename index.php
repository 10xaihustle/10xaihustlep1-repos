<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>10x AI Hustle P1 - Master AI Tools for Productivity</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 text-white font-sans antialiased">
    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-slate-900/95 backdrop-blur-xl border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <img src="https://10xaihustle.tsspages.com/media/images/media_69efc3f84c977_1777320952.png" alt="10x AI Hustle" class="h-8 w-auto">
                </div>
                <div class="flex items-center space-x-6">
                    <a href="<?= url('login.php') ?>" class="text-slate-300 hover:text-white transition-colors font-medium">Sign In</a>
                    <a href="<?= url('register.php') ?>" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-300 hover:scale-105 shadow-lg">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23334155" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
        
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 text-center">
            <div class="mb-8">
                <img src="https://10xaihustle.tsspages.com/media/images/media_69efc51f17d22_1777321247.png" alt="AI Everyday" class="mx-auto h-32 w-auto mb-8 opacity-90">
            </div>
            
            <h1 class="text-5xl lg:text-7xl font-extrabold mb-6 leading-tight">
                Master AI Tools for
                <span class="bg-gradient-to-r from-blue-400 to-purple-400 text-transparent bg-clip-text">
                    10x Productivity
                </span>
            </h1>
            
            <p class="text-xl lg:text-2xl text-slate-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                Track your AI tool usage, measure productivity gains, and unlock achievements as you transform your workflow with cutting-edge artificial intelligence.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="<?= url('register.php') ?>" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 hover:scale-105 shadow-2xl">
                    Start Your AI Journey
                </a>
                <a href="<?= url('login.php') ?>" class="bg-slate-800/50 border border-slate-700 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-700/50 transition-all duration-300 backdrop-blur-xl">
                    Sign In
                </a>
            </div>
            
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-2xl mx-auto">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-400 mb-2">50+</div>
                    <div class="text-slate-400">AI Tools Tracked</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-400 mb-2">10x</div>
                    <div class="text-slate-400">Productivity Boost</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-400 mb-2">24/7</div>
                    <div class="text-slate-400">Progress Tracking</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-slate-800/50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">Everything You Need to Master AI</h2>
                <p class="text-xl text-slate-300 max-w-3xl mx-auto">
                    Track your AI tool usage, measure productivity gains, and build the ultimate AI-powered workflow.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-blue-400 group-hover:text-blue-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">AI Tool Tracking</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Monitor your daily, weekly, and monthly usage of ChatGPT, Midjourney, Claude, and 50+ other AI tools. See which ones drive the most value.
                    </p>
                </div>
                
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-purple-400 group-hover:text-purple-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Productivity Sessions</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Log focused work sessions, track which AI tools you used, measure your productivity score, and identify your peak performance patterns.
                    </p>
                </div>
                
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-green-400 group-hover:text-green-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Achievement System</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Unlock badges and earn points as you master different AI tools. From "First Steps" to "AI Master" - gamify your productivity journey.
                    </p>
                </div>
                
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-orange-400 group-hover:text-orange-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Contact Management</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Organize your professional network, track leads, and manage client relationships as you build your AI-powered business.
                    </p>
                </div>
                
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-red-400 group-hover:text-red-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Analytics Dashboard</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Visualize your AI usage patterns, productivity trends, and ROI metrics with beautiful charts and insights.
                    </p>
                </div>
                
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 hover:bg-slate-700/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group">
                    <div class="mb-6">
                        <svg class="w-12 h-12 text-indigo-400 group-hover:text-indigo-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Learning Resources</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Access curated guides, tutorials, and best practices for maximizing your productivity with each AI tool in your arsenal.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="py-20 bg-slate-900">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">Join the AI Revolution</h2>
                <p class="text-xl text-slate-300 max-w-3xl mx-auto">
                    Thousands of professionals are already using AI to 10x their productivity. Don't get left behind.
                </p>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8">
                    <div class="text-4xl font-extrabold text-blue-400 mb-2" id="stat1">2,847</div>
                    <div class="text-slate-300">Active Users</div>
                </div>
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8">
                    <div class="text-4xl font-extrabold text-purple-400 mb-2" id="stat2">15,293</div>
                    <div class="text-slate-300">AI Sessions Tracked</div>
                </div>
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8">
                    <div class="text-4xl font-extrabold text-green-400 mb-2" id="stat3">8.7x</div>
                    <div class="text-slate-300">Avg Productivity Gain</div>
                </div>
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8">
                    <div class="text-4xl font-extrabold text-orange-400 mb-2" id="stat4">98%</div>
                    <div class="text-slate-300">User Satisfaction</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-slate-800/30">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-extrabold mb-6">How It Works</h2>
                <p class="text-xl text-slate-300 max-w-3xl mx-auto">
                    Start tracking your AI usage and boosting productivity in three simple steps.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Track Your AI Tools</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Add all the AI tools you use daily - ChatGPT, Midjourney, Claude, Copilot, and more. Set up your personal AI arsenal.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Log Productivity Sessions</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Record your focused work sessions, track which AI tools you used, and rate your productivity level after each session.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Optimize & Scale</h3>
                    <p class="text-slate-300 leading-relaxed">
                        Analyze your patterns, unlock achievements, and continuously optimize your AI workflow for maximum productivity gains.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Section -->
    <section class="py-20 bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-600 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-6xl font-extrabold mb-6">Ready to 10x Your Productivity?</h2>
            <p class="text-xl lg:text-2xl mb-8 opacity-90 leading-relaxed">
                Join thousands of professionals who are already using AI to transform their work. Start tracking your AI usage today.
            </p>
            <a href="<?= url('register.php') ?>" class="bg-slate-800/50 text-purple-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-slate-100 transition-all duration-300 hover:scale-105 shadow-2xl inline-block">
                Start Your Free Account
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <img src="https://10xaihustle.tsspages.com/media/images/media_69efc3f84c977_1777320952.png" alt="10x AI Hustle" class="h-8 w-auto opacity-80">
                </div>
                <div class="text-slate-400 text-center md:text-right">
                    <p>&copy; 2024 10x AI Hustle P1. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Animate counters
        function animateCounter(id, target) {
            const element = document.getElementById(id);
            const isDecimal = target.toString().includes('.');
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString();
                }
            }, 16);
        }
        
        // Intersection Observer for counter animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter('stat1', 2847);
                    animateCounter('stat2', 15293);
                    animateCounter('stat3', 8.7);
                    animateCounter('stat4', 98);
                    observer.disconnect();
                }
            });
        });
        
        observer.observe(document.getElementById('stat1'));
    </script>
</body>
</html>