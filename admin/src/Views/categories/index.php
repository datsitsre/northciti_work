

<style>
    .loading-spinner {
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .hierarchy-line {
        border-left: 2px solid #e5e7eb;
        margin-left: 1rem;
        padding-left: 1rem;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .color-picker {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        cursor: pointer;
    }
    
    .sortable-item {
        cursor: move;
    }
    
    .sortable-item:hover {
        background-color: #f9fafb;
    }
    
    .drag-handle {
        cursor: grab;
        color: #9ca3af;
    }
    
    .drag-handle:active {
        cursor: grabbing;
    }
</style>

<!-- Header -->
<div class="bg-white rounded-lg shadow-md border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col space-y-4 py-4 sm:flex-row sm:justify-between sm:items-center sm:space-y-0 sm:py-6">
            <div class="order-1 sm:order-1">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Categories Management</h1>
                <p class="text-xs text-gray-600 sm:text-sm">Manage content categories and their hierarchy</p>
            </div>
            <div class="order-2 sm:order-2 flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-3">
                <button id="toggleHierarchy" class="inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 sm:text-sm sm:px-4 sm:py-2">
                    <i class="fas fa-sitemap mr-1 sm:mr-2"></i>
                    <span class="whitespace-nowrap">Hierarchy View</span>
                </button>
                <button id="addCategoryBtn" class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 sm:text-sm sm:px-4 sm:py-2">
                    <i class="fas fa-plus mr-1 sm:mr-2"></i>
                    <span class="whitespace-nowrap">Add Category</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="max-w-1xl mx-auto py-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-2 lg:p-4 hover:shadow-xl hover:scale-105 transition-all duration-300 overflow-hidden ">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-folder text-2xl text-indigo-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Categories</dt>
                            <dd class="text-lg font-medium text-gray-900" id="totalCategories">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-2 lg:p-4 hover:shadow-xl hover:scale-105 transition-all duration-300 overflow-hidden ">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Categories</dt>
                            <dd class="text-lg font-medium text-gray-900" id="activeCategories">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-2 lg:p-4 hover:shadow-xl hover:scale-105 transition-all duration-300 overflow-hidden ">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-layer-group text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Parent Categories</dt>
                            <dd class="text-lg font-medium text-gray-900" id="parentCategories">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-2 lg:p-4 hover:shadow-xl hover:scale-105 transition-all duration-300 overflow-hidden ">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-sitemap text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Child Categories</dt>
                            <dd class="text-lg font-medium text-gray-900" id="childCategories">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 hover:shadow-xl mb-6">
        <div class="px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <div class="flex flex-wrap md:flex-nowrap items-center space-x-0 md:space-x-4 space-y-1">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search categories..." class="fomr-input block w-64 pl-10 pr-3 py-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                    
                    <select id="statusFilter" class="form-select block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    
                    <select id="parentFilter" class="form-select block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md">
                        <option value="">All Types</option>
                        <option value="parent">Parent Only</option>
                        <option value="child">Child Only</option>
                    </select>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button id="refreshBtn" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-refresh mr-2"></i>
                        Refresh
                    </button>
                    <button id="reorderBtn" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-sort mr-2"></i>
                        Reorder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories List/Grid -->
    <div class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 p-4 lg:p-6 hover:shadow-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Categories</h3>
                <div class="flex items-center space-x-2">
                    <button id="gridViewBtn" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-th"></i>
                    </button>
                    <button id="listViewBtn" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading State -->
        <div id="loadingState" class="flex justify-center items-center py-12">
            <div class="loading-spinner"></div>
            <span class="ml-2 text-gray-600">Loading categories...</span>
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" class="hidden text-center py-12">
            <i class="fas fa-folder-open text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
            <p class="text-gray-500 mb-4">Get started by creating your first category.</p>
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i>
                Add Category
            </button>
        </div>
        
        <!-- List View -->
        <div id="listView" class="divide-y divide-gray-200">
            <!-- Categories will be populated here -->
        </div>
        
        <!-- Grid View -->
        <div id="gridView" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
            <!-- Categories will be populated here -->
        </div>
        
        <!-- Hierarchy View -->
        <div id="hierarchyView" class="hidden p-6">
            <!-- Hierarchy will be populated here -->
        </div>
    </div>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Add Category</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="categoryForm" class="space-y-6">
                <input type="hidden" id="categoryId" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="categoryName" class="block text-sm font-medium text-gray-700 mb-2">
                            Category Name *
                        </label>
                        <input type="text" id="categoryName" name="name" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <div class="text-red-600 text-sm mt-1 hidden" id="nameError"></div>
                    </div>
                    
                    <div>
                        <label for="categorySlug" class="block text-sm font-medium text-gray-700 mb-2">
                            Slug
                        </label>
                        <input type="text" id="categorySlug" name="slug"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-500 mt-1">Auto-generated if left empty</p>
                    </div>
                </div>
                
                <div>
                    <label for="categoryDescription" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea id="categoryDescription" name="description" rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    <div class="text-red-600 text-sm mt-1 hidden" id="descriptionError"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="categoryColor" class="block text-sm font-medium text-gray-700 mb-2">
                            Color
                        </label>
                        <div class="flex items-center space-x-3">
                            <input type="color" id="categoryColor" name="color" value="#3B82F6"
                                   class="color-picker">
                            <input type="text" id="colorHex" placeholder="#3B82F6"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="text-red-600 text-sm mt-1 hidden" id="colorError"></div>
                    </div>
                    
                    <div>
                        <label for="categoryIcon" class="block text-sm font-medium text-gray-700 mb-2">
                            Icon
                        </label>
                        <div class="relative">
                            <input type="text" id="categoryIcon" name="icon" placeholder="fas fa-newspaper"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i id="iconPreview" class="fas fa-newspaper text-gray-400"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">FontAwesome class name</p>
                    </div>
                    
                    <div>
                        <label for="categorySortOrder" class="block text-sm font-medium text-gray-700 mb-2">
                            Sort Order
                        </label>
                        <input type="number" id="categorySortOrder" name="sort_order" min="0" value="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="parentCategory" class="block text-sm font-medium text-gray-700 mb-2">
                            Parent Category
                        </label>
                        <select id="parentCategory" name="parent_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">No Parent (Top Level)</option>
                        </select>
                        <div class="text-red-600 text-sm mt-1 hidden" id="parentError"></div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="flex items-center h-5">
                            <input id="categoryActive" name="is_active" type="checkbox" checked
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="categoryActive" class="font-medium text-gray-700">Active</label>
                            <p class="text-gray-500">Category is visible and can be used</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" id="cancelBtn" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <span id="submitText">Save Category</span>
                        <div class="loading-spinner ml-2 hidden" id="submitSpinner"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reorder Modal -->
<div id="reorderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">Reorder Categories</h3>
                <button id="closeReorderModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <p class="text-sm text-gray-600 mb-4">Drag and drop to reorder categories. Changes will be saved automatically.</p>
            
            <div id="sortableList" class="space-y-2 max-h-96 overflow-y-auto">
                <!-- Sortable items will be populated here -->
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                <button id="cancelReorder" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button id="saveReorder" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Save Order
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Notifications -->
<div id="notification" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notificationMessage">Success!</span>
        </div>
    </div>
</div>

<script>
    function getAuthToken() {
        return '<?= $_SESSION['auth_token'] ?>' || localStorage.getItem('auth_token');
    }

    // Configuration
    const API_BASE_URL = API_URL+'admin';
    const API_TOKEN = getAuthToken();
    
    // State
    let categories = [];
    let currentView = 'list';
    let isHierarchyView = false;
    let currentEditId = null;
    let parentCategories = [];
    
    // Initialize when DOM is loaded
    $(document).ready(function() {
        loadCategories();
        loadStatistics();
        setupEventListeners();
        setupFormValidation();
    });
    
    // API Helper Functions
    async function apiCall(endpoint, options = {}) {
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${API_TOKEN}`
            },
            ...options
        };
        
        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API call failed:', error);
            showNotification(error.message, 'error');
            throw error;
        }
    }
    
    // Load Categories
    async function loadCategories() {
        try {
            showLoading();
            
            const params = new URLSearchParams();
            if (isHierarchyView) {
                params.append('hierarchy', 'true');
            }
            
            const searchTerm = $('#searchInput').val();
            if (searchTerm) {
                // Use search endpoint
                params.append('q', searchTerm);
                const response = await apiCall(`/categories/search?${params}`);
                categories = response.data;
            } else {
                const response = await apiCall(`/categories?${params}`);
                categories = response.data;
            }
            
            // Filter categories based on current filters
            applyFilters();
            
            if (isHierarchyView) {
                renderHierarchyView();
            } else if (currentView === 'grid') {
                renderGridView();
            } else {
                renderListView();
            }
            
            hideLoading();
            
        } catch (error) {
            hideLoading();
            showEmptyState();
        }
    }
    
    // Load Statistics
    async function loadStatistics() {
        try {
            const response = await apiCall('/categories/statistics');
            const stats = response.data;
            
            $('#totalCategories').text(stats.total_categories);
            $('#activeCategories').text(stats.active_categories);
            $('#parentCategories').text(stats.parent_categories);
            $('#childCategories').text(stats.child_categories);
            
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    }
    
    // Apply Filters
    function applyFilters() {
        let filtered = [...categories];
        
        const statusFilter = $('#statusFilter').val();
        const parentFilter = $('#parentFilter').val();
        
        if (statusFilter !== '') {
            filtered = filtered.filter(cat => cat.is_active == statusFilter);
        }
        
        if (parentFilter === 'parent') {
            filtered = filtered.filter(cat => !cat.parent_id);
        } else if (parentFilter === 'child') {
            filtered = filtered.filter(cat => cat.parent_id);
        }
        
        categories = filtered;
    }
    
    // Render List View
    function renderListView() {
        const container = $('#listView');
        container.empty();
        
        if (categories.length === 0) {
            showEmptyState();
            return;
        }
        
        categories.forEach(category => {
            container.append(createListItem(category));
        });
        
        $('#gridView').addClass('hidden');
        $('#hierarchyView').addClass('hidden');
        $('#listView').removeClass('hidden');
    }
    
    // Render Grid View
    function renderGridView() {
        const container = $('#gridView');
        container.empty();
        
        if (categories.length === 0) {
            showEmptyState();
            return;
        }
        
        categories.forEach(category => {
            container.append(createGridItem(category));
        });
        
        $('#listView').addClass('hidden');
        $('#hierarchyView').addClass('hidden');
        $('#gridView').removeClass('hidden');
    }
    
    // Render Hierarchy View
    function renderHierarchyView() {
        const container = $('#hierarchyView');
        container.empty();
        
        if (categories.length === 0) {
            showEmptyState();
            return;
        }
        
        // Render hierarchy tree
        container.append(createHierarchyTree(categories));
        
        $('#listView').addClass('hidden');
        $('#gridView').addClass('hidden');
        $('#hierarchyView').removeClass('hidden');
    }
    
    // Create List Item
    function createListItem(category) {
        const parentName = category.parent_id ? 
            (categories.find(c => c.id === category.parent_id)?.name || 'Unknown') : 
            '-';
        
        return `
            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: ${category.color}">
                        <i class="${category.icon || 'fas fa-folder'} text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-sm font-medium text-gray-900">${category.name}</h4>
                            ${category.is_active ? 
                                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' :
                                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>'
                            }
                        </div>
                        <div class="text-sm text-gray-500">
                            <span>Parent: ${parentName}</span>
                            <span class="mx-2">•</span>
                            <span>Sort: ${category.sort_order}</span>
                            ${category.description ? `<span class="mx-2">•</span><span>${category.description.substring(0, 50)}${category.description.length > 50 ? '...' : ''}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="editCategory(${category.id})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>
                    </button>
                    <button onclick="deleteCategory(${category.id})" class="text-red-600 hover:text-red-900 text-sm font-medium">
                        <i class="fas fa-trash mr-1"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    // Create Grid Item
    function createGridItem(category) {
        const parentName = category.parent_id ? 
            (categories.find(c => c.id === category.parent_id)?.name || 'Unknown') : 
            'Top Level';
        
        return `
            <div class="category-card bg-white border border-gray-200 rounded-lg p-6 transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: ${category.color}">
                        <i class="${category.icon || 'fas fa-folder'} text-white text-lg"></i>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="editCategory(${category.id})" class="text-gray-400 hover:text-indigo-600">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCategory(${category.id})" class="text-gray-400 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-2">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">${category.name}</h3>
                    ${category.is_active ? 
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' :
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>'
                    }
                </div>
                
                ${category.description ? `<p class="text-sm text-gray-600 mb-3">${category.description.substring(0, 100)}${category.description.length > 100 ? '...' : ''}</p>` : ''}
                
                <div class="text-xs text-gray-500 space-y-1">
                    <div>Parent: ${parentName}</div>
                    <div>Sort Order: ${category.sort_order}</div>
                    <div>Slug: ${category.slug}</div>
                </div>
            </div>
        `;
    }
    
    // Create Hierarchy Tree
    function createHierarchyTree(items, level = 0) {
        let html = '';
        
        items.forEach(item => {
            const indent = level * 20;
            html += `
                <div class="flex items-center justify-between py-3 px-4 hover:bg-gray-50" style="margin-left: ${indent}px;">
                    <div class="flex items-center space-x-3">
                        ${level > 0 ? '<div class="w-4 h-px bg-gray-300"></div>' : ''}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: ${item.color}">
                            <i class="${item.icon || 'fas fa-folder'} text-white text-sm"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900">${item.name}</span>
                            ${item.is_active ? 
                                '<span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' :
                                '<span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>'
                            }
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="editCategory(${item.id})" class="text-indigo-600 hover:text-indigo-900 text-sm">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCategory(${item.id})" class="text-red-600 hover:text-red-900 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            if (item.children && item.children.length > 0) {
                html += createHierarchyTree(item.children, level + 1);
            }
        });
        
        return html;
    }
    
    // Event Listeners
    function setupEventListeners() {
        // Add Category Button
        $('#addCategoryBtn').click(() => openCategoryModal());
        
        // View Toggle Buttons
        $('#gridViewBtn').click(() => switchView('grid'));
        $('#listViewBtn').click(() => switchView('list'));
        $('#toggleHierarchy').click(() => toggleHierarchy());
        
        // Search Input
        $('#searchInput').on('input', debounce(loadCategories, 500));
        
        // Filter Dropdowns
        $('#statusFilter, #parentFilter').change(loadCategories);
        
        // Refresh Button
        $('#refreshBtn').click(loadCategories);
        
        // Reorder Button
        $('#reorderBtn').click(openReorderModal);
        
        // Modal Events
        $('#closeModal, #cancelBtn').click(closeCategoryModal);
        $('#closeReorderModal, #cancelReorder').click(closeReorderModal);
        
        // Form Events
        $('#categoryForm').submit(saveCategory);
        $('#categoryName').on('input', generateSlug);
        $('#categoryIcon').on('input', updateIconPreview);
        $('#categoryColor').on('input', updateColorHex);
        $('#colorHex').on('input', updateColorPicker);
        
        // Reorder Events
        $('#saveReorder').click(saveReorder);
        
        // Click outside modal to close
        $(window).click(function(event) {
            if (event.target.id === 'categoryModal') {
                closeCategoryModal();
            }
            if (event.target.id === 'reorderModal') {
                closeReorderModal();
            }
        });
    }
    
    // Form Validation
    function setupFormValidation() {
        $('#categoryName').on('blur', function() {
            const name = $(this).val().trim();
            if (!name) {
                showFieldError('name', 'Category name is required');
            } else if (name.length > 100) {
                showFieldError('name', 'Category name must not exceed 100 characters');
            } else {
                hideFieldError('name');
            }
        });
        
        $('#categoryDescription').on('blur', function() {
            const description = $(this).val().trim();
            if (description.length > 1000) {
                showFieldError('description', 'Description must not exceed 1000 characters');
            } else {
                hideFieldError('description');
            }
        });
        
        $('#colorHex').on('blur', function() {
            const color = $(this).val().trim();
            if (color && !/^#[a-fA-F0-9]{6}$/.test(color)) {
                showFieldError('color', 'Invalid color format (use #RRGGBB)');
            } else {
                hideFieldError('color');
            }
        });
    }
    
    // Utility Functions
    function switchView(view) {
        currentView = view;
        
        $('#gridViewBtn, #listViewBtn').removeClass('text-indigo-600 bg-indigo-100').addClass('text-gray-400');
        
        if (view === 'grid') {
            $('#gridViewBtn').removeClass('text-gray-400').addClass('text-indigo-600 bg-indigo-100');
            renderGridView();
        } else {
            $('#listViewBtn').removeClass('text-gray-400').addClass('text-indigo-600 bg-indigo-100');
            renderListView();
        }
    }
    
    function toggleHierarchy() {
        isHierarchyView = !isHierarchyView;
        
        if (isHierarchyView) {
            $('#toggleHierarchy span').text('List View');
            $('#toggleHierarchy i').removeClass('fa-sitemap').addClass('fa-list');
            renderHierarchyView();
        } else {
            $('#toggleHierarchy span').text('Hierarchy View');
            $('#toggleHierarchy i').removeClass('fa-list').addClass('fa-sitemap');
            if (currentView === 'grid') {
                renderGridView();
            } else {
                renderListView();
            }
        }
        
        loadCategories();
    }
    
    function generateSlug() {
        const name = $('#categoryName').val().trim();
        if (name && !currentEditId) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .trim('-');
            $('#categorySlug').val(slug);
        }
    }
    
    function updateIconPreview() {
        const icon = $('#categoryIcon').val().trim() || 'fas fa-folder';
        $('#iconPreview').attr('class', icon + ' text-gray-400');
    }
    
    function updateColorHex() {
        const color = $('#categoryColor').val();
        $('#colorHex').val(color);
    }
    
    function updateColorPicker() {
        const color = $('#colorHex').val().trim();
        if (/^#[a-fA-F0-9]{6}$/.test(color)) {
            $('#categoryColor').val(color);
        }
    }
    
    function showLoading() {
        $('#loadingState').removeClass('hidden');
        $('#emptyState').addClass('hidden');
        $('#listView, #gridView, #hierarchyView').addClass('hidden');
    }
    
    function hideLoading() {
        $('#loadingState').addClass('hidden');
    }
    
    function showEmptyState() {
        $('#emptyState').removeClass('hidden');
        $('#listView, #gridView, #hierarchyView').addClass('hidden');
    }
    
    function showFieldError(field, message) {
        $(`#${field}Error`).text(message).removeClass('hidden');
    }
    
    function hideFieldError(field) {
        $(`#${field}Error`).addClass('hidden');
    }
    
    function showNotification(message, type = 'success') {
        const notification = $('#notification');
        const notificationDiv = notification.find('div');
        
        notificationDiv.removeClass('bg-green-100 border-green-400 text-green-700 bg-red-100 border-red-400 text-red-700');
        
        if (type === 'error') {
            notificationDiv.addClass('bg-red-100 border-red-400 text-red-700');
            notificationDiv.find('i').removeClass('fa-check-circle').addClass('fa-exclamation-circle');
        } else {
            notificationDiv.addClass('bg-green-100 border-green-400 text-green-700');
            notificationDiv.find('i').removeClass('fa-exclamation-circle').addClass('fa-check-circle');
        }
        
        $('#notificationMessage').text(message);
        notification.removeClass('hidden').addClass('fade-in');
        
        setTimeout(() => {
            notification.addClass('hidden').removeClass('fade-in');
        }, 5000);
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Category Operations
    function openCategoryModal(categoryId = null) {
        currentEditId = categoryId;
        
        if (categoryId) {
            // Edit mode
            const category = categories.find(c => c.id === categoryId);
            if (category) {
                $('#modalTitle').text('Edit Category');
                $('#submitText').text('Update Category');
                
                // Fill form
                $('#categoryId').val(category.id);
                $('#categoryName').val(category.name);
                $('#categorySlug').val(category.slug);
                $('#categoryDescription').val(category.description || '');
                $('#categoryColor').val(category.color || '#3B82F6');
                $('#colorHex').val(category.color || '#3B82F6');
                $('#categoryIcon').val(category.icon || '');
                $('#categorySortOrder').val(category.sort_order || 0);
                $('#parentCategory').val(category.parent_id || '');
                $('#categoryActive').prop('checked', category.is_active);
                
                updateIconPreview();
            }
        } else {
            // Add mode
            $('#modalTitle').text('Add Category');
            $('#submitText').text('Create Category');
            $('#categoryForm')[0].reset();
            $('#categoryActive').prop('checked', true);
            $('#categoryColor').val('#3B82F6');
            $('#colorHex').val('#3B82F6');
            updateIconPreview();
        }
        
        // Load parent categories
        loadParentCategories(categoryId);
        
        $('#categoryModal').removeClass('hidden');
        $('#categoryName').focus();
    }
    
    function closeCategoryModal() {
        $('#categoryModal').addClass('hidden');
        currentEditId = null;
        $('#categoryForm')[0].reset();
        $('.text-red-600').addClass('hidden');
    }
    
    async function loadParentCategories(excludeId = null) {
        try {
            const response = await apiCall('/categories');
            parentCategories = response.data;
            
            const select = $('#parentCategory');
            select.find('option:not(:first)').remove();
            
            parentCategories
                .filter(cat => cat.id !== excludeId && !cat.parent_id) // Only top-level categories, exclude current
                .forEach(category => {
                    select.append(`<option value="${category.id}">${category.name}</option>`);
                });
        } catch (error) {
            console.error('Failed to load parent categories:', error);
        }
    }
    
    async function saveCategory(event) {
        event.preventDefault();
        
        // Clear previous errors
        $('.text-red-600').addClass('hidden');
        
        const formData = new FormData(event.target);
        const data = {
            name: formData.get('name').trim(),
            slug: formData.get('slug').trim(),
            description: formData.get('description').trim(),
            color: formData.get('color'),
            icon: formData.get('icon').trim(),
            parent_id: formData.get('parent_id') || null,
            sort_order: parseInt(formData.get('sort_order')) || 0,
            is_active: formData.has('is_active')
        };
        
        // Client-side validation
        let hasErrors = false;
        
        if (!data.name) {
            showFieldError('name', 'Category name is required');
            hasErrors = true;
        } else if (data.name.length > 100) {
            showFieldError('name', 'Category name must not exceed 100 characters');
            hasErrors = true;
        }
        
        if (data.description.length > 1000) {
            showFieldError('description', 'Description must not exceed 1000 characters');
            hasErrors = true;
        }
        
        if (data.color && !/^#[a-fA-F0-9]{6}$/.test(data.color)) {
            showFieldError('color', 'Invalid color format (use #RRGGBB)');
            hasErrors = true;
        }
        
        if (hasErrors) {
            return;
        }
        
        // Show loading
        $('#submitBtn').prop('disabled', true);
        $('#submitSpinner').removeClass('hidden');
        
        try {
            if (currentEditId) {
                // Update existing category
                await apiCall(`/categories/${currentEditId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                showNotification('Category updated successfully');
            } else {
                // Create new category
                await apiCall('/categories', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                showNotification('Category created successfully');
            }
            
            closeCategoryModal();
            loadCategories();
            loadStatistics();
            
        } catch (error) {
            // Handle validation errors
            if (error.message.includes('validation')) {
                // Parse validation errors if available
                // This would need to be implemented based on API response format
            }
        } finally {
            $('#submitBtn').prop('disabled', false);
            $('#submitSpinner').addClass('hidden');
        }
    }
    
    async function editCategory(categoryId) {
        openCategoryModal(categoryId);
    }
    
    async function deleteCategory(categoryId) {
        const category = categories.find(c => c.id === categoryId);
        if (!category) {
            return;
        }
        
        if (!confirm(`Are you sure you want to delete the category "${category.name}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            await apiCall(`/categories/${categoryId}`, {
                method: 'DELETE'
            });
            
            showNotification('Category deleted successfully');
            loadCategories();
            loadStatistics();
            
        } catch (error) {
            console.error('Failed to delete category:', error);
        }
    }
    
    // Reorder functionality
    function openReorderModal() {
        loadReorderList();
        $('#reorderModal').removeClass('hidden');
    }
    
    function closeReorderModal() {
        $('#reorderModal').addClass('hidden');
    }
    
    async function loadReorderList() {
        try {
            const response = await apiCall('/categories');
            const sortableCategories = response.data
                .filter(cat => !cat.parent_id) // Only parent categories for simplicity
                .sort((a, b) => a.sort_order - b.sort_order);
            
            const container = $('#sortableList');
            container.empty();
            
            sortableCategories.forEach((category, index) => {
                container.append(`
                    <div class="sortable-item flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md" data-id="${category.id}">
                        <div class="flex items-center space-x-3">
                            <i class="drag-handle fas fa-grip-vertical"></i>
                            <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: ${category.color}">
                                <i class="${category.icon || 'fas fa-folder'} text-white text-sm"></i>
                            </div>
                            <span class="font-medium text-gray-900">${category.name}</span>
                        </div>
                        <span class="text-sm text-gray-500">#${index + 1}</span>
                    </div>
                `);
            });
            
            // Make list sortable (you would need to include a sortable library like SortableJS)
            // For now, this is just the UI structure
            
        } catch (error) {
            console.error('Failed to load reorder list:', error);
        }
    }
    
    async function saveReorder() {
        // This would implement the reorder functionality
        // You'd need to collect the new order and send it to the API
        const order = [];
        $('#sortableList .sortable-item').each(function(index) {
            order.push(parseInt($(this).data('id')));
        });
        
        try {
            await apiCall('/categories/reorder', {
                method: 'POST',
                body: JSON.stringify({ order })
            });
            
            showNotification('Categories reordered successfully');
            closeReorderModal();
            loadCategories();
            
        } catch (error) {
            console.error('Failed to reorder categories:', error);
        }
    }
</script>
