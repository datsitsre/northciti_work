// admin/assets/js/comments.js - Comments Management JavaScript

$(document).ready(function () {
    let currentPage = 1;
    let currentFilters = {};
    let selectedComments = new Set();
    let currentCommentId = null;

    // Initialize
    loadComments();
    loadStatistics();

    // Event Listeners
    setupEventListeners();

    function setupEventListeners() {
        // Filter changes
        $(
            "#statusFilter, #contentTypeFilter, #priorityFilter, #flaggedFilter"
        ).on("change", function () {
            currentPage = 1;
            updateFilters();
            loadComments();
        });

        // Search
        $("#searchBtn").on("click", function () {
            currentPage = 1;
            updateFilters();
            loadComments();
        });

        $("#searchInput").on("keypress", function (e) {
            if (e.which === 13) {
                $("#searchBtn").click();
            }
        });

        // Clear filters
        $("#clearFiltersBtn").on("click", function () {
            $(
                "#statusFilter, #contentTypeFilter, #priorityFilter, #flaggedFilter"
            ).val("");
            $("#searchInput").val("");
            currentFilters = {};
            currentPage = 1;
            loadComments();
        });

        // Select all checkboxes
        $("#selectAll, #selectAllHeader").on("change", function () {
            const isChecked = $(this).is(":checked");
            $(".comment-checkbox").prop("checked", isChecked);

            if (isChecked) {
                $(".comment-checkbox").each(function () {
                    selectedComments.add(parseInt($(this).val()));
                });
            } else {
                selectedComments.clear();
            }

            updateSelectionCount();
            $("#selectAll, #selectAllHeader").prop("checked", isChecked);
        });

        // Individual comment selection
        $(document).on("change", ".comment-checkbox", function () {
            const commentId = parseInt($(this).val());

            if ($(this).is(":checked")) {
                selectedComments.add(commentId);
            } else {
                selectedComments.delete(commentId);
            }

            updateSelectionCount();

            // Update select all checkboxes
            const totalCheckboxes = $(".comment-checkbox").length;
            const checkedCheckboxes = $(".comment-checkbox:checked").length;
            $("#selectAll, #selectAllHeader").prop(
                "checked",
                totalCheckboxes === checkedCheckboxes
            );
        });

        // Comment actions
        $(document).on("click", ".view-comment", function () {
            const commentId = $(this).data("id");
            viewComment(commentId);
        });

        $(document).on("click", ".approve-comment", function () {
            const commentId = $(this).data("id");
            moderateComment(commentId, "approve");
        });

        $(document).on("click", ".reject-comment", function () {
            const commentId = $(this).data("id");
            moderateComment(commentId, "reject");
        });

        $(document).on("click", ".delete-comment", function () {
            const commentId = $(this).data("id");
            moderateComment(commentId, "delete");
        });

        // Bulk actions
        $("#bulkModerationBtn").on("click", function () {
            if (selectedComments.size === 0) {
                showToast("Please select comments first", "error");
                return;
            }
            $("#bulkSelectedCount").text(selectedComments.size);
            $("#bulkModal").removeClass("hidden");
        });

        $("#cancelBulkAction").on("click", function () {
            $("#bulkModal").addClass("hidden");
            $("#bulkAction").val("");
            $("#bulkReason").val("");
        });

        $("#executeBulkAction").on("click", function () {
            const action = $("#bulkAction").val();
            const reason = $("#bulkReason").val();

            if (!action) {
                showToast("Please select an action", "error");
                return;
            }

            executeBulkAction(action, reason);
        });

        // Auto moderate
        $("#autoModerateBtn").on("click", function () {
            if (
                confirm(
                    "This will automatically moderate pending comments based on AI analysis. Continue?"
                )
            ) {
                autoModerate();
            }
        });

        // Export
        $("#exportBtn").on("click", function () {
            exportComments();
        });

        // Modal actions
        $("#approveCommentBtn").on("click", function () {
            if (currentCommentId) {
                moderateComment(currentCommentId, "approve");
                $("#commentModal").addClass("hidden");
            }
        });

        $("#rejectCommentBtn").on("click", function () {
            if (currentCommentId) {
                moderateComment(currentCommentId, "reject");
                $("#commentModal").addClass("hidden");
            }
        });

        $("#deleteCommentBtn").on("click", function () {
            if (currentCommentId) {
                moderateComment(currentCommentId, "delete");
                $("#commentModal").addClass("hidden");
            }
        });

        $("#closeCommentModal").on("click", function () {
            $("#commentModal").addClass("hidden");
        });

        // Pagination
        $(document).on("click", ".pagination-btn", function () {
            const page = $(this).data("page");
            if (page && page !== currentPage) {
                currentPage = page;
                loadComments();
            }
        });
    }

    function updateFilters() {
        currentFilters = {
            status: $("#statusFilter").val(),
            content_type: $("#contentTypeFilter").val(),
            priority: $("#priorityFilter").val(),
            flagged_only: $("#flaggedFilter").val(),
            search: $("#searchInput").val(),
        };

        // Remove empty filters
        Object.keys(currentFilters).forEach((key) => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });
    }

    function loadComments() {
        $("#commentsLoading").removeClass("hidden");
        $("#commentsContainer").addClass("hidden");
        $("#commentsEmpty").addClass("hidden");

        const params = new URLSearchParams({
            page: currentPage,
            per_page: 20,
            ...currentFilters,
        });

        $.ajax({
            url: `/northcity/api/admin/moderation/queue?${params}`,
            method: "GET",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
            },
            success: function (response) {
                $("#commentsLoading").addClass("hidden");

                if (response.success && response.data.length > 0) {
                    renderComments(response.data);
                    renderPagination(response.meta.pagination);
                    $("#commentsContainer").removeClass("hidden");
                } else {
                    $("#commentsEmpty").removeClass("hidden");
                }
            },
            error: function (xhr) {
                $("#commentsLoading").addClass("hidden");
                $("#commentsEmpty").removeClass("hidden");
                console.error("Error loading comments:", xhr.responseJSON);
                showToast("Failed to load comments", "error");
            },
        });
    }

    function loadStatistics() {
        $.ajax({
            url: "/northcity/api/admin/moderation/statistics",
            method: "GET",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
            },
            success: function (response) {
                if (response.success) {
                    const stats = response.data;
                    $("#totalComments").text(
                        numberFormat(
                            stats.pending_items +
                                stats.approved_items +
                                stats.rejected_items
                        )
                    );
                    $("#pendingComments").text(
                        numberFormat(stats.pending_items)
                    );
                    $("#approvedComments").text(
                        numberFormat(stats.approved_items)
                    );
                    $("#flaggedComments").text(
                        numberFormat(stats.pending_flags)
                    );
                    $("#autoModeratedComments").text(
                        numberFormat(stats.comment_moderation_pending || 0)
                    );
                }
            },
            error: function (xhr) {
                console.error("Error loading statistics:", xhr.responseJSON);
            },
        });
    }

    function renderComments(comments) {
        const tbody = $("#commentsTableBody");
        tbody.empty();

        comments.forEach((comment) => {
            const row = createCommentRow(comment);
            tbody.append(row);
        });

        // Update selection state
        selectedComments.forEach((id) => {
            $(`.comment-checkbox[value="${id}"]`).prop("checked", true);
        });
        updateSelectionCount();
    }

    function createCommentRow(comment) {
        const statusClass = `status-${comment.status}`;
        const priorityClass = comment.priority
            ? `priority-${comment.priority}`
            : "";

        const moderationScore = comment.moderation_score || 0;
        const scoreClass = getModerationScoreClass(moderationScore);

        const contentPreview =
            comment.content.length > 100
                ? comment.content.substring(0, 100) + "..."
                : comment.content;

        return `
            <tr class="comment-item ${priorityClass}" data-id="${comment.id}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="comment-checkbox" value="${
                        comment.id
                    }">
                </td>
                <td class="px-6 py-4">
                    <div class="max-w-xs">
                        <p class="text-sm text-gray-900 truncate">${escapeHtml(
                            contentPreview
                        )}</p>
                        <div class="flex items-center mt-2 space-x-2">
                            ${
                                comment.is_flagged
                                    ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-flag mr-1"></i> Flagged</span>'
                                    : ""
                            }
                            ${
                                comment.auto_moderated
                                    ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-robot mr-1"></i> Auto</span>'
                                    : ""
                            }
                        </div>
                        ${
                            moderationScore > 0
                                ? `
                            <div class="mt-2">
                                <div class="moderation-score">
                                    <div class="moderation-score-fill ${scoreClass}" style="width: ${
                                      moderationScore * 100
                                  }%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Risk: ${(
                                    moderationScore * 100
                                ).toFixed(1)}%</p>
                            </div>
                        `
                                : ""
                        }
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            ${
                                comment.profile_image
                                    ? `<img class="h-8 w-8 rounded-full" src="${comment.profile_image}" alt="">`
                                    : `<div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 text-sm"></i>
                                </div>`
                            }
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">${escapeHtml(
                                comment.author_name || "Anonymous"
                            )}</p>
                            <p class="text-sm text-gray-500">${escapeHtml(
                                comment.username ||
                                    comment.author_email ||
                                    "Guest"
                            )}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                        <div class="font-medium">${escapeHtml(
                            comment.content_title || "Unknown"
                        )}</div>
                        <div class="text-gray-500 capitalize">${
                            comment.content_type
                        }</div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                        ${
                            comment.status.charAt(0).toUpperCase() +
                            comment.status.slice(1)
                        }
                    </span>
                    ${
                        comment.priority
                            ? `<div class="text-xs text-gray-500 mt-1 capitalize">${comment.priority}</div>`
                            : ""
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="space-y-1">
                        ${
                            comment.upvotes || comment.downvotes
                                ? `
                            <div class="flex items-center space-x-2">
                                <span class="text-green-600"><i class="fas fa-thumbs-up"></i> ${
                                    comment.upvotes || 0
                                }</span>
                                <span class="text-red-600"><i class="fas fa-thumbs-down"></i> ${
                                    comment.downvotes || 0
                                }</span>
                            </div>
                        `
                                : ""
                        }
                        ${
                            comment.flag_count > 0
                                ? `<div class="text-red-600 text-xs"><i class="fas fa-flag"></i> ${comment.flag_count} flags</div>`
                                : ""
                        }
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>${formatDate(comment.created_at)}</div>
                    ${
                        comment.updated_at !== comment.created_at
                            ? `<div class="text-xs">Updated: ${formatDate(
                                  comment.updated_at
                              )}</div>`
                            : ""
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2">
                        <button class="view-comment text-blue-600 hover:text-blue-900" data-id="${
                            comment.id
                        }" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${
                            comment.status === "pending"
                                ? `
                            <button class="approve-comment text-green-600 hover:text-green-900" data-id="${comment.id}" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="reject-comment text-red-600 hover:text-red-900" data-id="${comment.id}" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `
                                : ""
                        }
                        <button class="delete-comment text-gray-600 hover:text-gray-900" data-id="${
                            comment.id
                        }" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderPagination(pagination) {
        const container = $("#commentsPagination");

        if (pagination.total_pages <= 1) {
            container.empty();
            return;
        }

        let paginationHtml = `
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing ${
                        (pagination.current_page - 1) * pagination.per_page + 1
                    } to ${Math.min(
            pagination.current_page * pagination.per_page,
            pagination.total
        )} of ${pagination.total} results
                </div>
                <div class="flex space-x-2">
        `;

        // Previous button
        if (pagination.has_previous) {
            paginationHtml += `<button class="pagination-btn px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" data-page="${
                pagination.current_page - 1
            }">Previous</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(
            pagination.total_pages,
            pagination.current_page + 2
        );

        if (startPage > 1) {
            paginationHtml += `<button class="pagination-btn px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="px-3 py-2">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.current_page;
            paginationHtml += `<button class="pagination-btn px-3 py-2 border rounded-md ${
                isActive
                    ? "bg-blue-600 text-white border-blue-600"
                    : "border-gray-300 hover:bg-gray-50"
            }" data-page="${i}">${i}</button>`;
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHtml += `<span class="px-3 py-2">...</span>`;
            }
            paginationHtml += `<button class="pagination-btn px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" data-page="${pagination.total_pages}">${pagination.total_pages}</button>`;
        }

        // Next button
        if (pagination.has_next) {
            paginationHtml += `<button class="pagination-btn px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50" data-page="${
                pagination.current_page + 1
            }">Next</button>`;
        }

        paginationHtml += `
                </div>
            </div>
        `;

        container.html(paginationHtml);
    }

    function viewComment(commentId) {
        currentCommentId = commentId;

        $.ajax({
            url: `/northcity/api/admin/moderation/comments/${commentId}`,
            method: "GET",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
            },
            success: function (response) {
                if (response.success) {
                    renderCommentModal(response.data);
                    $("#commentModal").removeClass("hidden");
                }
            },
            error: function (xhr) {
                console.error(
                    "Error loading comment details:",
                    xhr.responseJSON
                );
                showToast("Failed to load comment details", "error");
            },
        });
    }

    function renderCommentModal(comment) {
        const moderationScore = comment.moderation_score || 0;
        const scoreClass = getModerationScoreClass(moderationScore);

        const modalContent = `
            <div class="space-y-6">
                <!-- Comment Content -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Comment Content</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-800">${escapeHtml(
                            comment.content
                        )}</p>
                    </div>
                </div>

                <!-- Author Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Author Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <p class="text-sm text-gray-900">${escapeHtml(
                                comment.author_name || "Anonymous"
                            )}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="text-sm text-gray-900">${escapeHtml(
                                comment.author_email || "Not provided"
                            )}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <p class="text-sm text-gray-900">${escapeHtml(
                                comment.username || "Guest"
                            )}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">User Role</label>
                            <p class="text-sm text-gray-900 capitalize">${
                                comment.role || "Guest"
                            }</p>
                        </div>
                    </div>
                </div>

                <!-- Content Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Related Content</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content Type</label>
                            <p class="text-sm text-gray-900 capitalize">${
                                comment.content_type
                            }</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content Title</label>
                            <p class="text-sm text-gray-900">${escapeHtml(
                                comment.content_title || "Unknown"
                            )}</p>
                        </div>
                    </div>
                </div>

                <!-- Moderation Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Moderation Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-${
                                comment.status
                            }">
                                ${
                                    comment.status.charAt(0).toUpperCase() +
                                    comment.status.slice(1)
                                }
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <p class="text-sm text-gray-900 capitalize">${
                                comment.priority || "Normal"
                            }</p>
                        </div>
                        ${
                            moderationScore > 0
                                ? `
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Risk Score</label>
                                <div class="mt-1">
                                    <div class="moderation-score">
                                        <div class="moderation-score-fill ${scoreClass}" style="width: ${
                                      moderationScore * 100
                                  }%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">${(
                                        moderationScore * 100
                                    ).toFixed(1)}% risk</p>
                                </div>
                            </div>
                        `
                                : ""
                        }
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Auto Moderated</label>
                            <p class="text-sm text-gray-900">${
                                comment.auto_moderated ? "Yes" : "No"
                            }</p>
                        </div>
                    </div>
                </div>

                <!-- Engagement -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Engagement</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Upvotes</label>
                            <p class="text-sm text-green-600"><i class="fas fa-thumbs-up mr-1"></i> ${
                                comment.upvotes || 0
                            }</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Downvotes</label>
                            <p class="text-sm text-red-600"><i class="fas fa-thumbs-down mr-1"></i> ${
                                comment.downvotes || 0
                            }</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Flags</label>
                            <p class="text-sm text-red-600"><i class="fas fa-flag mr-1"></i> ${
                                comment.flag_count || 0
                            }</p>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Timestamps</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created</label>
                            <p class="text-sm text-gray-900">${formatDate(
                                comment.created_at
                            )}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                            <p class="text-sm text-gray-900">${formatDate(
                                comment.updated_at
                            )}</p>
                        </div>
                    </div>
                </div>

                <!-- Technical Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Technical Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">IP Address</label>
                            <p class="text-sm text-gray-900 font-mono">${
                                comment.ip_address || "Not recorded"
                            }</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">User Agent</label>
                            <p class="text-sm text-gray-900 truncate" title="${
                                comment.user_agent || "Not recorded"
                            }">${comment.user_agent || "Not recorded"}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("#commentModalContent").html(modalContent);
    }

    function moderateComment(commentId, action) {
        const confirmMessages = {
            approve: "Are you sure you want to approve this comment?",
            reject: "Are you sure you want to reject this comment?",
            delete: "Are you sure you want to delete this comment? This action cannot be undone.",
        };

        if (!confirm(confirmMessages[action])) {
            return;
        }

        $.ajax({
            url: `/northcity/api/admin/moderation/comments/${commentId}`,
            method: "PUT",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
                "Content-Type": "application/json",
            },
            data: JSON.stringify({
                action: action,
                reason: `Admin ${action} via dashboard`,
            }),
            success: function (response) {
                if (response.success) {
                    showToast(`Comment ${action}d successfully`, "success");
                    loadComments();
                    loadStatistics();
                } else {
                    showToast(
                        response.message || `Failed to ${action} comment`,
                        "error"
                    );
                }
            },
            error: function (xhr) {
                console.error(`Error ${action}ing comment:`, xhr.responseJSON);
                showToast(`Failed to ${action} comment`, "error");
            },
        });
    }

    function executeBulkAction(action, reason) {
        const commentIds = Array.from(selectedComments);

        $.ajax({
            url: "/northcity/api/admin/moderation/bulk",
            method: "POST",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
                "Content-Type": "application/json",
            },
            data: JSON.stringify({
                comment_ids: commentIds,
                action: action,
                reason: reason,
            }),
            success: function (response) {
                if (response.success) {
                    showToast(
                        `Bulk ${action} completed: ${response.data.success} successful, ${response.data.failed} failed`,
                        "success"
                    );
                    $("#bulkModal").addClass("hidden");
                    selectedComments.clear();
                    loadComments();
                    loadStatistics();
                    updateSelectionCount();
                } else {
                    showToast(
                        response.message || `Bulk ${action} failed`,
                        "error"
                    );
                }
            },
            error: function (xhr) {
                console.error("Error executing bulk action:", xhr.responseJSON);
                showToast(`Bulk ${action} failed`, "error");
            },
        });
    }

    function autoModerate() {
        $.ajax({
            url: "/northcity/api/admin/moderation/auto-moderate",
            method: "POST",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
            },
            success: function (response) {
                if (response.success) {
                    const result = response.data;
                    showToast(
                        `Auto-moderation completed: ${result.processed} processed, ${result.approved} approved, ${result.rejected} rejected`,
                        "success"
                    );
                    loadComments();
                    loadStatistics();
                } else {
                    showToast("Auto-moderation failed", "error");
                }
            },
            error: function (xhr) {
                console.error("Error auto-moderating:", xhr.responseJSON);
                showToast("Auto-moderation failed", "error");
            },
        });
    }

    function exportComments() {
        const params = new URLSearchParams({
            format: "csv",
            timeframe: "30",
            ...currentFilters,
        });

        $.ajax({
            url: `/northcity/api/admin/comments/export?${params}`,
            method: "GET",
            headers: {
                Authorization: `Bearer ${getAuthToken()}`,
            },
            success: function (response) {
                if (response.success) {
                    // Create download link
                    const link = document.createElement("a");
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    showToast("Export completed successfully", "success");
                } else {
                    showToast("Export failed", "error");
                }
            },
            error: function (xhr) {
                console.error("Error exporting comments:", xhr.responseJSON);
                showToast("Export failed", "error");
            },
        });
    }

    function updateSelectionCount() {
        $("#selectedCount").text(selectedComments.size);
    }

    function getModerationScoreClass(score) {
        if (score >= 0.8) return "score-critical";
        if (score >= 0.6) return "score-high";
        if (score >= 0.3) return "score-medium";
        return "score-low";
    }

    function showToast(message, type = "success") {
        const toast = $("#toast");
        const toastMessage = $("#toastMessage");

        // Set message
        toastMessage.text(message);

        // Set color based on type
        const toastDiv = toast.find("div");
        toastDiv.removeClass("bg-green-600 bg-red-600 bg-yellow-600");

        switch (type) {
            case "error":
                toastDiv.addClass("bg-red-600");
                break;
            case "warning":
                toastDiv.addClass("bg-yellow-600");
                break;
            default:
                toastDiv.addClass("bg-green-600");
        }

        // Show toast
        toast.removeClass("hidden");

        // Hide after 5 seconds
        setTimeout(() => {
            toast.addClass("hidden");
        }, 5000);
    }

    function getAuthToken() {
        // This should get the actual auth token from your authentication system
        return (
            localStorage.getItem("admin_token") ||
            sessionStorage.getItem("admin_token") ||
            ""
        );
    }

    function escapeHtml(text) {
        const map = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
        };
        return text.replace(/[&<>"']/g, function (m) {
            return map[m];
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + " " + date.toLocaleTimeString();
    }

    function numberFormat(num) {
        return new Intl.NumberFormat().format(num);
    }
});
