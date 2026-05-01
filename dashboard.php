<?php
require_once 'config.php';
require_once 'includes/functions.php';
requireAuth();

$user = getCurrentUser();
$db = getDB();

// Get statistics
$stats = [];
try {
    $stats['total_contacts'] = $db->query('SELECT COUNT(*) FROM contacts WHERE user_id = ' . (int)$user['id'])->fetchColumn();
    $stats['active_tools'] = $db->query('SELECT COUNT(*) FROM ai_tools WHERE user_id = ' . (int)$user['id'] . ' AND is_favorite = 1')->fetchColumn();
    $stats['productivity_sessions'] = $db->query('SELECT COUNT(*) FROM productivity_sessions WHERE user_id = ' . (int)$user['id'])->fetchColumn();
    $stats['total_achievements'] = $db->query('SELECT COUNT(*) FROM achievements WHERE user_id = ' . (int)$user['id'])->fetchColumn();
    
    // Recent activity
    $recent_contacts = $db->prepare('SELECT first_name, last_name, company, created_at FROM contacts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $recent_contacts->execute([$user['id']]);
    $recent_contacts = $recent_contacts->fetchAll();
    
    $recent_tools = $db->prepare('SELECT tool_name, category, updated_at FROM ai_tools WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5');
    $recent_tools->execute([$user['id']]);
    $recent_tools = $recent_tools->fetchAll();
    
    $recent_sessions = $db->prepare('SELECT session_name, duration_minutes, productivity_score, created_at FROM productivity_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $recent_sessions->execute([$user['id']]);
    $recent_sessions = $recent_sessions->fetchAll();
} catch (Exception $e) {
    $stats = ['total_contacts' => 0, 'active_tools' => 0, 'productivity_sessions' => 0, 'total_achievements' => 0];
    $recent_contacts = [];
    $recent_tools = [];
    $recent_sessions = [];
}

require_once 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="space-y-8">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-extrabold mb-2">Welcome back, <?= e($user['name'] ?? $user['username'] ?? 'Hustler') ?>!</h1>
                    <p class="text-xl text-indigo-100">Ready to 10x your productivity with AI? Let's dominate today.</p>
                </div>
                <img src="https://10xaihustle.tsspages.com/media/images/media_69efc51f17d22_1777321247.png" alt="10x AI Hustle" class="w-24 h-24 rounded-full border-4 border-slate-600/50 object-cover">
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 9a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-blue-400" id="contacts-counter"><?= $stats['total_contacts'] ?></span>
            </div>
            <h3 class="text-lg font-semibold text-white">Total Contacts</h3>
            <p class="text-sm text-slate-400">Your growing network</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-purple-400" id="tools-counter"><?= $stats['active_tools'] ?></span>
            </div>
            <h3 class="text-lg font-semibold text-white">Favorite AI Tools</h3>
            <p class="text-sm text-slate-400">Your power arsenal</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-green-400" id="sessions-counter"><?= $stats['productivity_sessions'] ?></span>
            </div>
            <h3 class="text-lg font-semibold text-white">Productivity Sessions</h3>
            <p class="text-sm text-slate-400">Your grind sessions</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-yellow-500/20 rounded-xl">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-yellow-400" id="achievements-counter"><?= $stats['total_achievements'] ?></span>
            </div>
            <h3 class="text-lg font-semibold text-white">Achievements</h3>
            <p class="text-sm text-slate-400">Your victories</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="<?= url('contacts.php') ?>" class="group bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-8 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl hover:border-blue-500/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-4 bg-blue-500/20 rounded-2xl group-hover:bg-blue-500/30 transition-colors">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Manage Contacts</h3>
            <p class="text-slate-400">Build your network, track leads, grow your hustle</p>
        </a>

        <a href="<?= url('ai-tools.php') ?>" class="group bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-8 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl hover:border-purple-500/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-4 bg-purple-500/20 rounded-2xl group-hover:bg-purple-500/30 transition-colors">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">AI Tools Arsenal</h3>
            <p class="text-slate-400">Discover, track, and master AI tools that 10x your output</p>
        </a>

        <a href="<?= url('productivity.php') ?>" class="group bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-8 hover:scale-[1.02] transition-all duration-300 hover:shadow-xl hover:border-green-500/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-4 bg-green-500/20 rounded-2xl group-hover:bg-green-500/30 transition-colors">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Track Sessions</h3>
            <p class="text-slate-400">Log your productivity sessions and level up your hustle game</p>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Contacts -->
        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Recent Contacts</h3>
                <a href="<?= url('contacts.php') ?>" class="text-blue-400 hover:text-blue-300 text-sm font-medium">View All</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($recent_contacts)): ?>
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-slate-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 9a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-slate-400">No contacts yet. Start building your network!</p>
                        <a href="<?= url('contacts.php') ?>" class="inline-block mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">Add First Contact</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_contacts as $contact): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-700/50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?= strtoupper(substr($contact['first_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-white"><?= e($contact['first_name'] . ' ' . $contact['last_name']) ?></p>
                                    <p class="text-sm text-slate-400"><?= e($contact['company'] ?? 'No company') ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500"><?= timeAgo($contact['created_at']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent AI Tools Activity -->
        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">AI Tools Activity</h3>
                <a href="<?= url('ai-tools.php') ?>" class="text-purple-400 hover:text-purple-300 text-sm font-medium">View All</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($recent_tools)): ?>
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-slate-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <p class="text-slate-400">No AI tools tracked yet. Start building your arsenal!</p>
                        <a href="<?= url('ai-tools.php') ?>" class="inline-block mt-3 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">Add First Tool</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_tools as $tool): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-700/50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white"><?= e($tool['tool_name']) ?></p>
                                    <p class="text-sm text-slate-400"><?= e($tool['category'] ?? 'Uncategorized') ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500"><?= timeAgo($tool['updated_at']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Productivity Chart -->
    <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
        <h3 class="text-xl font-bold text-white mb-6">Weekly Productivity Trend</h3>
        <div class="h-64">
            <canvas id="productivityChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Animated counters
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 20);
}

// Animate all counters on load
document.addEventListener('DOMContentLoaded', function() {
    animateCounter(document.getElementById('contacts-counter'), <?= $stats['total_contacts'] ?>);
    animateCounter(document.getElementById('tools-counter'), <?= $stats['active_tools'] ?>);
    animateCounter(document.getElementById('sessions-counter'), <?= $stats['productivity_sessions'] ?>);
    animateCounter(document.getElementById('achievements-counter'), <?= $stats['total_achievements'] ?>);
    
    // Productivity Chart
    const ctx = document.getElementById('productivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Productivity Score',
                data: [75, 85, 90, 88, 95, 78, 82],
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>