<?php

    // admin/src/Views/dashboard/index.php - Updated Dashboard View (Content Only)

    // Prevent direct access
    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }
?>

<!-- Header -->
<header class="bg-white rounded-xl shadow-lg border-b border-gray-200 px-6 py-4 mb-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800"><?= $GLOBALS['pageTitle']; ?></h2>
            <p class="text-gray-600"><?= $GLOBALS['pageSubtitle']; ?></p>
        </div>
    </div>
</header>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Users Card -->
    <div class="stat-card group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Users</p>
                <p class="text-3xl font-bold text-gray-900" data-stat="total_users">
                    <?= LayoutHelper::formatNumber($stats['users']['total_users'] ?? 0) ?>
                </p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-green-600 font-medium">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <?= LayoutHelper::formatNumber($stats['users']['new_users_month'] ?? 0) ?>
                    </span>
                    <span class="text-sm text-gray-500 ml-1">this month</span>
                </div>
            </div>
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 ">
                <i class="fas fa-users text-blue-800 text-xl"></i>
            </div>
        </div>
        
        <!-- Mini chart or additional info -->
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Active: <?= LayoutHelper::formatNumber($stats['users']['active_users'] ?? 0) ?></span>
                <span class="text-orange-600">Pending: <?= LayoutHelper::formatNumber($stats['users']['pending_users'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- News Card -->
    <div class="stat-card group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">News Articles</p>
                <p class="text-3xl font-bold text-gray-900" data-stat="total_news">
                    <?= LayoutHelper::formatNumber($stats['news']['total_news'] ?? 0) ?>
                </p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-orange-600 font-medium">
                        <i class="fas fa-clock mr-1"></i>
                        <?= LayoutHelper::formatNumber($stats['news']['pending_news'] ?? 0) ?>
                    </span>
                    <span class="text-sm text-gray-500 ml-1">pending review</span>
                </div>
            </div>
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-emerald-300 to-green-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-newspaper text-green-800 text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Published: <?= LayoutHelper::formatNumber($stats['news']['published_news'] ?? 0) ?></span>
                <span class="text-blue-600">This week: <?= LayoutHelper::formatNumber($stats['news']['new_news_week'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- Events Card -->
    <div class="stat-card group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Events</p>
                <p class="text-3xl font-bold text-gray-900" data-stat="total_events">
                    <?= LayoutHelper::formatNumber($stats['events']['total_events'] ?? 0) ?>
                </p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-purple-600 font-medium">
                        <i class="fas fa-calendar mr-1"></i>
                        <?= LayoutHelper::formatNumber($stats['events']['upcoming_events'] ?? 0) ?>
                    </span>
                    <span class="text-sm text-gray-500 ml-1">upcoming</span>
                </div>
            </div>
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-purple-300 to-violet-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-calendar-alt text-purple-800 text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Published: <?= LayoutHelper::formatNumber($stats['events']['published_events'] ?? 0) ?></span>
                <span class="text-orange-600">Pending: <?= LayoutHelper::formatNumber($stats['events']['pending_events'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- Comments Card -->
    <div class="stat-card group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 mb-1">Comments</p>
                <p class="text-3xl font-bold text-gray-900" data-stat="total_comments">
                    <?= LayoutHelper::formatNumber($stats['comments']['total_comments'] ?? 0) ?>
                </p>
                <div class="flex items-center mt-2">
                    <span class="text-sm text-red-600 font-medium">
                        <i class="fas fa-flag mr-1"></i>
                        <?= LayoutHelper::formatNumber($stats['comments']['pending_comments'] ?? 0) ?>
                    </span>
                    <span class="text-sm text-gray-500 ml-1">need review</span>
                </div>
            </div>
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-pink-500 to-red-500 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-comments text-red-800 text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Approved: <?= LayoutHelper::formatNumber($stats['comments']['approved_comments'] ?? 0) ?></span>
                <span class="text-red-600">Flagged: <?= LayoutHelper::formatNumber($stats['comments']['flagged_comments'] ?? 0) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Recent Activity -->
    <div class="lg:col-span-2 group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl ">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                <a href="<?= Router::url('activities') ?>" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                    View all <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="p-6">
            <?php $recentActivity = DashboardController::getRecentActivity(); ?>
            <?php if (empty($recentActivity)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-history text-3xl mb-2"></i>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <?php
                                    $actionIcons = [
                                        'admin_login' => 'fas fa-sign-in-alt text-green-600',
                                        'admin_logout' => 'fas fa-sign-out-alt text-red-600',
                                        'user_created' => 'fas fa-user-plus text-blue-600',
                                        'news_published' => 'fas fa-newspaper text-green-600',
                                        'event_published' => 'fas fa-calendar text-purple-600',
                                        'comment_moderated' => 'fas fa-comment text-orange-600'
                                    ];
                                    $icon = $actionIcons[$activity['action']] ?? 'fas fa-circle text-gray-600';
                                    ?>
                                    <i class="<?= $icon ?>"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">
                                    <span class="font-medium"><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></span>
                                    <?= ucfirst(str_replace('_', ' ', $activity['action'])) ?>
                                </p>
                                <p class="text-xs text-gray-500"><?= LayoutHelper::timeAgo($activity['created_at']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl ">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <a href="<?= Router::url('moderation') ?>" 
                   class="flex items-center p-3 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition-colors group">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 group-hover:text-indigo-700">Moderate Contents</p>
                        <p class="text-sm text-gray-500">Manage and moderste content</p>
                    </div>
                </a>

                <a href="<?= Router::url('events') ?>" 
                   class="flex items-center p-3 rounded-lg bg-green-50 hover:bg-green-100 transition-colors group">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-plus text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 group-hover:text-green-700">Add New Event</p>
                        <p class="text-sm text-gray-500">Schedule upcoming events</p>
                    </div>
                </a>

                <a href="<?= Router::url('users') ?>" 
                   class="flex items-center p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors group">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 group-hover:text-blue-700">Manage Users</p>
                        <p class="text-sm text-gray-500">View and moderate users</p>
                    </div>
                </a>

                <?php if ($stats['comments']['pending_comments'] > 0): ?>
                <a href="<?= Router::url('comments') ?>" 
                   class="flex items-center p-3 rounded-lg bg-orange-50 hover:bg-orange-100 transition-colors group">
                    <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-comments text-white"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 group-hover:text-orange-700">Review Comments</p>
                        <p class="text-sm text-gray-500"><?= $stats['comments']['pending_comments'] ?> pending approval</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
    </div>
    <div class="p-6">
        <?php $systemInfo = DashboardController::getSystemInfo(); ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900"><?= $systemInfo['php_version'] ?></div>
                <div class="text-sm text-gray-500">PHP Version</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    <?= round($systemInfo['memory_usage'] / 1024 / 1024, 1) ?>MB
                </div>
                <div class="text-sm text-gray-500">Memory Usage</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    <?= round($systemInfo['disk_free_space'] / 1024 / 1024 / 1024, 1) ?>GB
                </div>
                <div class="text-sm text-gray-500">Free Space</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="text-sm text-gray-500">System Status</div>
            </div>
        </div>
    </div>
</div>