// admin/assets/js/media.js - Media Library JavaScript

class MediaLibrary {
    constructor() {
        this.selectedItems = new Set();
        this.currentView = 'grid';
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateBulkActions();
        this.loadStats();
    }

    bindEvents() {
        // Checkbox selection
        $(document).on('change', '.media-checkbox', (e) => {
            this.handleSelection(e.target);
        });

        // View toggle
        $(document).on('click', '[data-view]', (e) => {
            this.switchView(e.target.dataset.view);
        });

        // Filter changes
        $('#filterForm select, #filterForm input').on('change input', 
            this.debounce(() => this.applyFilters(), 500)
        );

        // Bulk actions
        $('#bulkActionsBtn').on('click', () => this.showBulkModal());

        // Upload progress
        if (typeof Dropzone !== 'undefined') {
            this.initializeDropzone();
        }

        // Keyboard shortcuts
        $(document).on('keydown', (e) => this.handleKeyboard(e));
    }

    handleSelection(checkbox) {
        const id = parseInt(checkbox.dataset.id);
        const card = checkbox.closest('.media-card');

        if (checkbox.checked) {
            this.selectedItems.add(id);
            card.classList.add('selected');
        } else {
            this.selectedItems.delete(id);
            card.classList.remove('selected');
        }

        this.updateBulkActions();
    }

    updateBulkActions() {
        const count = this.selectedItems.size;
        const $button = $('#bulkActionsBtn');

        if (count > 0) {
            $button.prop('disabled', false)
                   .html(`<i class="fas fa-check-square"></i> Selected (${count})`);
        } else {
            $button.prop('disabled', true)
                   .html('<i class="fas fa-tasks"></i> Bulk Actions');
        }
    }

    switchView(view) {
        this.currentView = view;
        const $grid = $('#mediaGrid');
        const $buttons = $('[data-view]');

        // Update button states
        $buttons.removeClass('active');
        $(`[data-view="${view}"]`).addClass('active');

        // Update grid layout
        if (view === 'grid') {
            $grid.removeClass('list-view').addClass('row');
        } else {
            $grid.removeClass('row').addClass('list-view');
        }

        // Save preference
        localStorage.setItem('media_view', view);
    }

    applyFilters() {
        $('#filterForm').submit();
    }

    clearFilters() {
        $('#filterForm')[0].reset();
        window.location.href = window.location.pathname;
    }

    showBulkModal() {
        if (this.selectedItems.size === 0) return;

        $('#selectedCount').text(this.selectedItems.size);
        $('#bulkModal').modal('show');
    }

    async executeBulkAction(action) {
        if (this.selectedItems.size === 0) return;

        const ids = Array.from(this.selectedItems);
        const confirmText = this.getBulkConfirmText(action, ids.length);

        if (!confirm(confirmText)) return;

        try {
            this.showLoading(true);
            
            const response = await this.apiRequest('POST', '/admin/media/bulk-action', {
                ids: ids,
                action: action,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
                
                if (action === 'delete') {
                    // Remove deleted items from view
                    ids.forEach(id => {
                        $(`.media-card[data-id="${id}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    });
                }
                
                this.selectedItems.clear();
                this.updateBulkActions();
                $('#bulkModal').modal('hide');
                
                // Reload page after a delay for other actions
                if (action !== 'delete') {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                throw new Error(response.message || 'Bulk action failed');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    getBulkConfirmText(action, count) {
        const messages = {
            approve: `Are you sure you want to approve ${count} selected items?`,
            reject: `Are you sure you want to reject ${count} selected items? This action cannot be undone.`,
            delete: `Are you sure you want to delete ${count} selected items? This action cannot be undone.`
        };
        
        return messages[action] || `Are you sure you want to ${action} ${count} selected items?`;
    }

    async moderateMedia(id, action) {
        const actionText = action === 'approve' ? 'approve' : 'reject';
        
        if (!confirm(`Are you sure you want to ${actionText} this media file?`)) {
            return;
        }

        try {
            const response = await this.apiRequest('POST', '/admin/media/moderate', {
                id: id,
                action: action,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
                
                // Update the card status
                this.updateMediaCardStatus(id, action === 'approve' ? 'approved' : 'rejected');
            } else {
                throw new Error(response.message || 'Moderation failed');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    async deleteMedia(id) {
        if (!confirm('Are you sure you want to delete this media file? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await this.apiRequest('POST', '/admin/media/delete', {
                id: id,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            });

            if (response.success) {
                this.showNotification(response.message, 'success');
                
                // Remove the card from view
                $(`.media-card[data-id="${id}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                throw new Error(response.message || 'Delete failed');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    updateMediaCardStatus(id, status) {
        const $card = $(`.media-card[data-id="${id}"]`);
        const $badge = $card.find('.badge');
        
        const statusClasses = {
            approved: 'badge-success',
            pending: 'badge-warning',
            rejected: 'badge-secondary',
            flagged: 'badge-danger'
        };

        // Update badge
        $badge.removeClass('badge-success badge-warning badge-secondary badge-danger')
              .addClass(statusClasses[status] || 'badge-secondary')
              .text(status.toUpperCase());

        // Update border for pending/flagged items
        if (status === 'pending') {
            $card.addClass('border-warning');
        } else if (status === 'flagged') {
            $card.addClass('border-danger');
        } else {
            $card.removeClass('border-warning border-danger');
        }
    }

    async loadStats() {
        try {
            const response = await this.apiRequest('GET', '/admin/media/stats');
            if (response.success && response.data) {
                this.updateStatsDisplay(response.data);
            }
        } catch (error) {
            console.error('Failed to load media stats:', error);
        }
    }

    updateStatsDisplay(stats) {
        // Update statistics cards with real-time data
        $('#totalFiles').text(this.formatNumber(stats.statistics?.total_files || 0));
        $('#totalImages').text(this.formatNumber(stats.statistics?.image_count || 0));
        $('#totalDocs').text(this.formatNumber(stats.statistics?.document_count || 0));
        $('#storageUsed').text(this.formatBytes(stats.statistics?.total_size || 0));
        $('#pendingReview').text(this.formatNumber(stats.pending_review?.length || 0));
    }

    initializeDropzone() {
        if (!$('#uploadDropzone').length) return;

        const dropzone = new Dropzone('#uploadDropzone', {
            url: '/admin/media/upload',
            maxFilesize: 10, // MB
            acceptedFiles: 'image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.zip',
            addRemoveLinks: true,
            dictDefaultMessage: 'Drop files here or click to upload',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: (file, response) => {
                if (response.success) {
                    this.showNotification('File uploaded successfully', 'success');
                } else {
                    this.showNotification(response.message || 'Upload failed', 'error');
                }
            },
            error: (file, message) => {
                this.showNotification(typeof message === 'string' ? message : 'Upload failed', 'error');
            }
        });

        return dropzone;
    }

    handleKeyboard(e) {
        // Ctrl+A - Select all
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            this.selectAll();
        }
        
        // Delete key - Delete selected
        if (e.key === 'Delete' && this.selectedItems.size > 0) {
            e.preventDefault();
            this.executeBulkAction('delete');
        }
        
        // Escape - Clear selection
        if (e.key === 'Escape') {
            this.clearSelection();
        }
    }

    selectAll() {
        $('.media-checkbox').prop('checked', true).trigger('change');
    }

    clearSelection() {
        $('.media-checkbox').prop('checked', false).trigger('change');
    }

    async apiRequest(method, url, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    showNotification(message, type = 'info') {
        const alertClass = {
            success: 'alert-success',
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        }[type] || 'alert-info';

        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${this.getNotificationIcon(type)} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);

        $('.content').prepend(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        return icons[type] || 'fa-info-circle';
    }

    showLoading(show) {
        if (show) {
            $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>');
        } else {
            $('.loading-overlay').remove();
        }
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    debounce(func, wait) {
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
}

// Global functions for backward compatibility
let mediaLibrary;

$(document).ready(function() {
    mediaLibrary = new MediaLibrary();
});

// Global functions called from HTML
function viewMedia(id) {
    $.get(`/admin/media/show?id=${id}`, function(data) {
        $('#mediaModalBody').html(data);
        $('#mediaModal').modal('show');
    }).fail(function() {
        mediaLibrary.showNotification('Failed to load media details', 'error');
    });
}

function editMedia(id) {
    window.location.href = `/admin/media/edit?id=${id}`;
}

function downloadMedia(id) {
    window.open(`/admin/media/download?id=${id}`, '_blank');
}

function moderateMedia(id, action) {
    mediaLibrary.moderateMedia(id, action);
}

function deleteMedia(id) {
    mediaLibrary.deleteMedia(id);
}

function executeBulkAction(action) {
    mediaLibrary.executeBulkAction(action);
}

function switchView(view) {
    mediaLibrary.switchView(view);
}

function clearFilters() {
    mediaLibrary.clearFilters();
}

function submitFilters() {
    mediaLibrary.applyFilters();
}



