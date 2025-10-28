<?php

    // admin/src/Views/activities/show.php - Activity Details View

    if (!defined('ADMIN_ACCESS')) {
        die('Direct access not permitted');
    }
?>

<!-- Main Content -->
<div class="flex-1 overflow-y-auto">
    <!-- Header -->
    <header class="bg-white rounded-xl shadow-lg border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?= $GLOBALS['pageTitle']; ?></h2>
                <p class="text-gray-600"><?= $GLOBALS['pageSubtitle']; ?></p>
            </div>
            <a href="<?= Router::url('activities') ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-all flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Activities
            </a>
        </div>
    </header>

    <!-- Activity Details -->
    <main class="py-6">
        <div class="max-w-1xl mx-auto">
            <div class="detail-section rounded-xl shadow-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Basic Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Activity ID</label>
                            <p class="text-gray-900">#<?= $activity['id'] ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Action</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))) ?>
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Date & Time</label>
                            <p class="text-gray-900"><?= date('F j, Y \a\t g:i:s A', strtotime($activity['created_at'])) ?></p>
                            <p class="text-sm text-gray-500"><?= timeAgo($activity['created_at']) ?></p>
                        </div>

                        <?php if (!empty($activity['target_type'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Target Type</label>
                                <p class="text-gray-900"><?= htmlspecialchars(ucwords($activity['target_type'])) ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Target ID</label>
                                <p class="text-gray-900"><?= $activity['target_id'] ?></p>
                            </div>

                            <?php if (!empty($activity['target_title'])): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Target Title</label>
                                    <p class="text-gray-900"><?= htmlspecialchars($activity['target_title']) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- User Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">User Information</h3>
                        
                        <?php if (!empty($activity['username'])): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($activity['username']) ?></p>
                                    <?php if (!empty($activity['first_name'])): ?>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($activity['email'])): ?>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['email']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-cog text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">System</p>
                                    <p class="text-sm text-gray-500">Automated system action</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">IP Address</label>
                            <p class="text-gray-900 font-mono"><?= htmlspecialchars($activity['ip_address']) ?></p>
                        </div>

                        <?php if (!empty($activity['user_agent'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">User Agent</label>
                                <p class="text-gray-900 text-sm break-all"><?= htmlspecialchars($activity['user_agent']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Metadata Section -->
                <?php if (!empty($activity['metadata'])): ?>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2 mb-4">Additional Metadata</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-sm text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars(json_encode(json_decode($activity['metadata'], true), JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>