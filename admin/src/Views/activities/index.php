<?php

    // admin/src/Views/activities/index.php - Activities List View

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
            <div class="flex space-x-3">
                <button id="exportBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
                <button id="cleanupBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all flex items-center">
                    <i class="fas fa-trash mr-2"></i>
                    Cleanup
                </button>
            </div>
        </div>
    </header>

    <!-- Activities Content -->
    <main class="py-6">
        <?php if (!empty($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?= htmlspecialchars($_GET['success']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Activities</p>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_activities']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-list text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Today</p>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['today_count']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">This Week</p>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['week_count']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-week text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['unique_users']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" action="<?= Router::url('activities') ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $actionOption): ?>
                            <option value="<?= htmlspecialchars($actionOption) ?>" <?= ($_GET['action'] ?? '') === $actionOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $actionOption))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        <?php foreach ($users as $userOption): ?>
                            <option value="<?= $userOption['id'] ?>" <?= ($_GET['user_id'] ?? '') == $userOption['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($userOption['username']) ?> (<?= htmlspecialchars($userOption['first_name'] . ' ' . $userOption['last_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Type</label>
                    <select name="target_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($targetTypes as $typeOption): ?>
                            <option value="<?= htmlspecialchars($typeOption) ?>" <?= ($_GET['target_type'] ?? '') === $typeOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucwords($typeOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                           class="form-select"
                           placeholder="Search activities...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" 
                           class="form-select">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" 
                           class="form-select">
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all flex items-center">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>
                    <a href="<?= Router::url('activities') ?>" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-all flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Activities Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($activities['data'])): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p>No activities found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activities['data'] as $activity): ?>
                                <tr class="activity-row hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600 text-sm"></i>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($activity['username'] ?? 'System') ?>
                                                </div>
                                                <?php if (!empty($activity['first_name'])): ?>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($activity['target_type'])): ?>
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars(ucwords($activity['target_type'])) ?>
                                                <?php if (!empty($activity['target_title'])): ?>
                                                    <span class="text-gray-500">- <?= htmlspecialchars(substr($activity['target_title'], 0, 30)) ?><?= strlen($activity['target_title']) > 30 ? '...' : '' ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-sm text-gray-500">ID: <?= $activity['target_id'] ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($activity['ip_address']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div><?= date('M j, Y', strtotime($activity['created_at'])) ?></div>
                                        <div class="text-xs text-gray-400"><?= date('H:i:s', strtotime($activity['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?= Router::url('activities/show') ?>?id=<?= $activity['id'] ?>" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($activities['pagination']['total_pages'] > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <?= (($activities['pagination']['current_page'] - 1) * $activities['pagination']['per_page']) + 1 ?> 
                                to <?= min($activities['pagination']['current_page'] * $activities['pagination']['per_page'], $activities['pagination']['total']) ?> 
                                of <?= number_format($activities['pagination']['total']) ?> results
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($activities['pagination']['has_previous']): ?>
                                <a href="<?= Router::url('activities') ?>?<?= http_build_query(array_merge($_GET, ['page' => $activities['pagination']['current_page'] - 1])) ?>" 
                                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($activities['pagination']['has_next']): ?>
                                <a href="<?= Router::url('activities') ?>?<?= http_build_query(array_merge($_GET, ['page' => $activities['pagination']['current_page'] + 1])) ?>" 
                                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Export Activities</h3>
            <button id="closeExportModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="exportForm" method="GET" action="<?= Router::url('activities/export') ?>">
            <!-- Copy current filters to export form -->
            <?php foreach ($_GET as $key => $value): ?>
                <?php if ($key !== 'page'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                <select name="format" class="form-select">
                    <option value="csv">CSV</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelExport" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cleanup Modal -->
<div id="cleanupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Cleanup Old Activities</h3>
            <button id="closeCleanupModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?= Router::url('activities/cleanup') ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Delete activities older than</label>
                <select name="days" class="form-select">
                    <option value="30">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90" selected>90 days</option>
                    <option value="180">180 days</option>
                    <option value="365">1 year</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">This action cannot be undone.</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelCleanup" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const exportModal = document.getElementById('exportModal');
    const cleanupModal = document.getElementById('cleanupModal');
    
    document.getElementById('exportBtn').addEventListener('click', () => {
        exportModal.classList.remove('hidden');
        exportModal.classList.add('flex');
    });
    
    document.getElementById('cleanupBtn').addEventListener('click', () => {
        cleanupModal.classList.remove('hidden');
        cleanupModal.classList.add('flex');
    });
    
    document.getElementById('closeExportModal').addEventListener('click', () => {
        exportModal.classList.add('hidden');
        exportModal.classList.remove('flex');
    });
    
    document.getElementById('closeCleanupModal').addEventListener('click', () => {
        cleanupModal.classList.add('hidden');
        cleanupModal.classList.remove('flex');
    });
    
    document.getElementById('cancelExport').addEventListener('click', () => {
        exportModal.classList.add('hidden');
        exportModal.classList.remove('flex');
    });
    
    document.getElementById('cancelCleanup').addEventListener('click', () => {
        cleanupModal.classList.add('hidden');
        cleanupModal.classList.remove('flex');
    });
    
    // Close modals when clicking outside
    exportModal.addEventListener('click', (e) => {
        if (e.target === exportModal) {
            exportModal.classList.add('hidden');
            exportModal.classList.remove('flex');
        }
    });
    
    cleanupModal.addEventListener('click', (e) => {
        if (e.target === cleanupModal) {
            cleanupModal.classList.add('hidden');
            cleanupModal.classList.remove('flex');
        }
    });
</script>
