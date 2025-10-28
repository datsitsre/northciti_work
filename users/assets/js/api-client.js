// users/assets/js/api-client.js - Enhanced API Client for Real Data

class APIClient {
    constructor(baseUrl = API_CONFIG.baseUrl) {
        this.baseUrl = baseUrl;
        this.defaultHeaders = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization':`Bearer ${API_CONFIG.token}`
        };
        
        // Add CSRF token if available
        if (window.APP_CONFIG?.csrfToken) {
            this.defaultHeaders['X-CSRF-Token'] = window.APP_CONFIG.csrfToken;
        }
    }

    /**
     * Make HTTP request with proper error handling
     */
    async request(endpoint, options = {}) {
        const path = this.isAuthorized() && '/' || '/public/';
        const url = new URL(`${this.baseUrl}${path}${endpoint.replace(/^\//, '')}`);
        
        const config = {
            method: options.method || 'GET',
            headers: {
                ...this.defaultHeaders,
                ...options.headers
            }
        };

        // Add query parameters for GET requests
        if (config.method === 'GET' && options.params) {
            Object.keys(options.params).forEach(key => {
                if (options.params[key] !== null && options.params[key] !== undefined) {
                    url.searchParams.append(key, options.params[key]);
                }
            });
        }

        // Add body for non-GET requests
        if (config.method !== 'GET' && options.data) {
            config.body = JSON.stringify(options.data);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new APIError(data.message || `HTTP ${response.status}`, response.status, data);
            }

            return data;
        } catch (error) {
            if (error instanceof APIError) {
                throw error;
            }
            throw new APIError('Network error occurred', 0, { originalError: error });
        }
    }

    // Convenience methods
    async get(endpoint, params = {}, options = {}) {
        return this.request(endpoint, { method: 'GET', params, ...options });
    }

    async post(endpoint, data = {}, options = {}) {
        return this.request(endpoint, { method: 'POST', data, ...options });
    }

    async put(endpoint, data = {}, options = {}) {
        return this.request(endpoint, { method: 'PUT', data, ...options });
    }

    async delete(endpoint, options = {}) {
        return this.request(endpoint, { method: 'DELETE', ...options });
    }

    // News API methods
    async getNews(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 20,
            per_page: 20,
            page: 1
        };
        return this.get('news', { ...defaultParams, ...params });
    }

    async getFeaturedNews(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 5
        };
        return this.get('news/featured', { ...defaultParams, ...params });
    }

    async getBreakingNews(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 10
        };
        return this.get('news/breaking', { ...defaultParams, ...params });
    }

    async getNewsById(id) {
        if (!id) throw new Error('News ID is required');
        return this.get(`news/${id}`);
    }

    async getNewsBySlug(slug) {
        if (!slug) throw new Error('News slug is required');
        return this.get(`news/${slug}`);
    }

    async getNewsByCategory(categorySlug, params = {}) {
        if (!categorySlug) throw new Error('Category slug is required');
        const defaultParams = {
            status: 'published',
            limit: 20
        };
        return this.get(`news/category/${categorySlug}`, { ...defaultParams, ...params });
    }

    async getNewsByTag(tagSlug, params = {}) {
        if (!tagSlug) throw new Error('Tag slug is required');
        const defaultParams = {
            status: 'published',
            limit: 20
        };
        return this.get(`news/tag/${tagSlug}`, { ...defaultParams, ...params });
    }

    // Events API methods
    async getEvents(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 20,
            page: 1
        };
        return this.get('events', { ...defaultParams, ...params });
    }

    async getFeaturedEvents(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 5
        };
        return this.get('events/featured', { ...defaultParams, ...params });
    }

    async getUpcomingEvents(params = {}) {
        const defaultParams = {
            status: 'published',
            limit: 10
        };
        return this.get('events/upcoming', { ...defaultParams, ...params });
    }

    async getEventById(id) {
        if (!id) throw new Error('Event ID is required');
        return this.get(`events/${id}`);
    }

    async getEventsByCategory(categorySlug, params = {}) {
        if (!categorySlug) throw new Error('Category slug is required');
        const defaultParams = {
            status: 'published',
            limit: 20
        };
        return this.get(`events/category/${categorySlug}`, { ...defaultParams, ...params });
    }

    // Categories API methods
    async getCategories(params = {}) {
        return this.get('categories', params);
    }

    async getCategoryById(id) {
        if (!id) throw new Error('Category ID is required');
        return this.get(`categories/${id}`);
    }

    async getPopularCategories(params = {}) {
        return this.get('categories/popular', params);
    }

    // Tags API methods
    async getTags(params = {}) {
        return this.get('tags', params);
    }

    async getTagBySlug(slug) {
        if (!slug) throw new Error('Tag slug is required');
        return this.get(`tags/${slug}`);
    }

    // Search API methods
    async search(query, params = {}) {
        if (!query) throw new Error('Search query is required');
        const defaultParams = {
            limit: 20,
            page: 1
        };
        return this.get('search', { q: query, ...defaultParams, ...params });
    }

    async getSearchSuggestions(query) {
        if (!query) throw new Error('Search query is required');
        return this.get('search/suggestions', { q: query });
    }

    async getPopularSearches() {
        return this.get('search/popular');
    }

    async addViewCounts(contentType, contentId) {
        if (!contentId) throw new Error('Content id is required');
        return this.post(`${contentType}/${contentId}/increase-view`);
    }

    // User interaction methods (require authentication)
    async likeContent(contentType, contentId) {
        this.requireAuth();
        return this.post(`${contentType}/${contentId}/like`);
    }

    async unlikeContent(contentType, contentId) {
        this.requireAuth();
        return this.delete(`${contentType}/${contentId}/like`);
    }

    async bookmarkContent(contentType, contentId) {
        this.requireAuth();
        return this.post(`${contentType}/${contentId}/bookmark`);
    }

    async removeBookmark(contentType, contentId) {
        this.requireAuth();
        return this.delete(`${contentType}/${contentId}/bookmark`);
    }

    async checkLikeStatus(contentType, contentId) {
        return this.get(`${contentType}/${contentId}/like-status`);
    }

    async checkBookmarkStatus(contentType, contentId) {
        return this.get(`${contentType}/${contentId}/bookmark-status`);
    }

    async checkAttendanceStatus(contentType, contentId) {
        return this.get(`${contentType}/${contentId}/attendance-status`);
    }

    async attendEvent(eventId) {
        this.requireAuth();
        return this.post(`events/${eventId}/attend`);
    }

    async unattendEvent(eventId) {
        this.requireAuth();
        return this.delete(`events/${eventId}/attend`);
    }

    // Comment methods
    async getComments(contentType, contentId, params = {}) {
        const defaultParams = {
            limit: 20,
            page: 1
        };
        return this.get(`${contentType}/${contentId}/comments`, { ...defaultParams, ...params });
    }

    async postComment(contentType, contentId, content, parentId = null) {
        this.requireAuth();
        const data = {
            "content_type":contentType,
            "content":content,
            "content_id":contentId,
            "user_id": window.APP_CONFIG?.currentUser.id,
            parent_id: parentId
        };
        return this.post(`${contentType}/${contentId}/comments`, data);
    }

    async updateComment(commentId, content) {
        this.requireAuth();
        return this.put(`comments/${commentId}`, { content });
    }

    async deleteComment(commentId) {
        this.requireAuth();
        return this.delete(`comments/${commentId}`);
    }

    async voteComment(commentId, voteType) {
        this.requireAuth();
        return this.post(`comments/${commentId}/vote`, { vote_type: voteType });
    }

    async flagComment(commentId, flagType, reason) {
        this.requireAuth();
        return this.post(`comments/${commentId}/flag`, {
            flag_type: flagType,
            reason
        });
    }

    // User profile methods
    async getUserProfile() {
        this.requireAuth();
        return this.get('users/me');
    }

    async updateUserProfile(data) {
        this.requireAuth();
        return this.put('users/me', data);
    }

    async getUserBookmarks(params = {}) {
        this.requireAuth();
        const defaultParams = {
            limit: 20,
            page: 1
        };
        return this.get('users/me/bookmarks', { ...defaultParams, ...params });
    }

    async getUserActivity(params = {}) {
        this.requireAuth();
        const defaultParams = {
            limit: 20,
            page: 1
        };
        return this.get('users/me/activity', { ...defaultParams, ...params });
    }

    // Utility methods
    requireAuth() {
        if (!window.APP_CONFIG?.currentUser) {
            throw new Error('Authentication required');
        }
    }
    isAuthorized() {
        return window.APP_CONFIG?.currentUser;
    }


    static getAuthorInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'A';
    }

    static formatContent(content) {
        if (!content) return '';
        
        // Convert line breaks to paragraphs and handle basic HTML
        return content
            .split('\n\n')
            .map(paragraph => {
                if (paragraph.trim()) {
                    return `<p class="mb-4">${paragraph.replace(/\n/g, '<br>')}</p>`;
                }
                return '';
            })
            .join('');
    }

    // Static helper methods for common operations
    static formatImageUrl(imagePath, baseUrl = UPLOADS_URL) {
        if (!imagePath) {
            return 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&h=300&fit=crop';
        }
        if (imagePath == undefined || imagePath == "undefined" || imagePath == "") {
            return 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&h=300&fit=crop';
        }
        
        if (imagePath.startsWith('http')) {
            return imagePath;
        }
        
        return `${baseUrl + imagePath}`;
    }

    static formatDate(dateString) {
        if (!dateString) return 'Unknown';
        
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

    static formatTime(timeString) {
        if (!timeString) return 'Time TBA';
        const time = new Date(`2000-01-01T${timeString}`);
        return time.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }

    static formatArticle(rawArticle) {
        return {
            id: rawArticle.id,
            title: rawArticle.title || 'Untitled Article',
            excerpt: rawArticle.summary || rawArticle.excerpt || this.truncateText(rawArticle.content || '', 150),
            image: this.formatImageUrl(rawArticle.featured_image || rawArticle.image),
            category: rawArticle.category_name || rawArticle.category_slug || rawArticle.category || 'News',
            categoryIcon: this.getCategoryIcon(rawArticle.category_name || rawArticle.category),
            author: {
                id: rawArticle.author_id || null,
                name: rawArticle.first_name || rawArticle.author || 'Anonymous',
                role: rawArticle.role || 'Contributor',
                avatar: this.formatImageUrl(rawArticle.profile_image)
            },
            publishedAt: this.formatDate(rawArticle.published_at || rawArticle.created_at),
            readTime: this.getReadingTime(rawArticle.content || rawArticle.title || ''),
            views: this.formatNumber(rawArticle.view_count || rawArticle.views || 0),
            likes: this.formatNumber(rawArticle.like_count || rawArticle.likes || 0),
            shares: this.formatNumber(rawArticle.share_count || rawArticle.shares || 0),
            bookmarks: this.formatNumber(rawArticle.bookmark_count || rawArticle.bookmarks || 0),
            comments: this.formatNumber(rawArticle.comment_count || rawArticle.comments || 0),
            slug: rawArticle.slug
        };
    }

    static formatNumber(num) {
        if (!num) return '0';
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    }

    static truncateText(text, maxLength = 150) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength).trim() + '...';
    }

    static getCategoryColor(categoryName) {
        const colors = {
            'General News': 'bg-fuchsia-600/40 text-fuchsia-600 border-fuchsia-400/50',
            'Technology': 'bg-blue-600/40 text-blue-600 border-blue-400/50',
            'Politics': 'bg-red-600/40 text-red-600 border-red-400/50',
            'Sports': 'bg-green-600/40 text-green-600 border-green-400/50',
            'Business': 'bg-yellow-600/40 text-yellow-600 border-yellow-400/50',
            'Entertainment': 'bg-purple-600/40 text-purple-600 border-purple-400/50',
            'Health': 'bg-pink-600/40 text-pink-600 border-pink-400/50',
            'Education': 'bg-indigo-600/40 text-indigo-600 border-indigo-400/50',
            'Science': 'bg-teal-600/40 text-teal-600 border-teal-400/50',
            'World': 'bg-cyan-600/40 text-cyan-600 border-cyan-400/50',
            'Local': 'bg-orange-600/40 text-orange-600 border-orange-400/50'
        };
        return colors[categoryName] || 'bg-gray-500/20 text-gray-400 border-gray-400/50';
    }

    static getCategoryIcon(category) {
        const icons = {
            'General News': 'fas fa-newspaper',
            'Technology': 'fas fa-microchip',
            'Politics': 'fas fa-globe-americas',
            'Sports': 'fas fa-medal',
            'Business': 'fas fa-chart-line',
            'Entertainment': 'fas fa-film',
            'Health': 'fas fa-heart',
            'Science': 'fas fa-flask',
            'World': 'fas fa-globe',
            'Local': 'fas fa-map-marker-alt'
        };
        
        return icons[category] || 'fas fa-newspaper';
    }

    static openArticle(param) {
        // Handle article click - could navigate to full article
        window.location.href = `${APP_URL}news/article/${param}`;
    }

    static getReadingTime(content) {
        if (!content) return '1 min read'; // if empty content
        if (content.length < 10) return `${content} min read`; // sometimes the readtime is already calculated in db
        const wordsPerMinute = 200;
        const wordCount = content.split(/\s+/).length;
        const minutes = Math.ceil(wordCount / wordsPerMinute);
        return `${minutes} min read`;
    }
}

// Custom Error class for API errors
class APIError extends Error {
    constructor(message, status = 0, data = {}) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.data = data;
    }
}

// Create global API client instance
window.apiClient = new APIClient();

// Expose APIClient class globally for custom instances
window.APIClient = APIClient;
window.APIError = APIError;

// Global utility functions that use the API client
window.apiUtils = {
    // Quick data fetchers
    async getLatestNews(limit = 10) {
        try {
            const response = await window.apiClient.getNews({ limit, sort: 'created_at', order: 'desc' });
            return response.data || response || [];
        } catch (error) {
            console.error('Failed to fetch latest news:', error);
            return [];
        }
    },

    async getFeaturedContent() {
        try {
            const [news, events] = await Promise.all([
                window.apiClient.getFeaturedNews({ limit: 3 }),
                window.apiClient.getFeaturedEvents({ limit: 3 })
            ]);
            return {
                news: news.data || news || [],
                events: events.data || events || []
            };
        } catch (error) {
            console.error('Failed to fetch featured content:', error);
            return { news: [], events: [] };
        }
    },

    async getUpcomingEvents(limit = 8) {
        try {
            const response = await window.apiClient.getUpcomingEvents({ limit });
            const events = response.data || response || [];
            // Filter for future events
            return events.filter(event => new Date(event.start_date) >= new Date());
        } catch (error) {
            console.error('Failed to fetch upcoming events:', error);
            return [];
        }
    },

    async getAllCategories() {
        try {
            const response = await window.apiClient.getCategories();
            return response.data || response || [];
        } catch (error) {
            console.error('Failed to fetch categories:', error);
            return [];
        }
    },

    // Content interaction helpers
    async toggleEventAttendance(eventId, isAttending = false) {
        if (!window.APP_CONFIG?.currentUser) {
            throw new Error('Please sign in to attend event');
        }

        try {
            let response = [];
            if (isAttending) {
                response = await window.apiClient.attendEvent(eventId);
                return response;
            } else {
                response = await window.apiClient.unattendEvent(eventId);
                return response;
            }
        } catch (error) {
            console.error('Failed to toggle event attendance:', error);
            throw error;
        }
    },

    // Content interaction helpers
    async toggleLike(contentType, contentId, currentlyLiked = false) {
        if (!window.APP_CONFIG?.currentUser) {
            throw new Error('Please sign in to like content');
        }

        try {
            let response = [];
            if (currentlyLiked) {
                response = await window.apiClient.unlikeContent(contentType, contentId);
                return response;
            } else {
                response = await window.apiClient.likeContent(contentType, contentId);
                return response;
            }
        } catch (error) {
            console.error('Failed to toggle like:', error);
            throw error;
        }
    },

    async toggleBookmark(contentType, contentId, currentlyBookmarked = false) {
        if (!window.APP_CONFIG?.currentUser) {
            throw new Error('Please sign in to bookmark content');
        }

        try {
            let response = [];
            if (currentlyBookmarked) {
                response = await window.apiClient.removeBookmark(contentType, contentId);
                return response;
            } else {
                response = await window.apiClient.bookmarkContent(contentType, contentId);
                return response;
            }
        } catch (error) {
            console.error('Failed to toggle bookmark:', error);
            throw error;
        }
    },

    // Notification system
    async showNotification(message, type = 'info', duration = 3000) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(notification => notification.remove());
        
        const toast = document.createElement('div');
        toast.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full max-w-sm`;
        
        // Set background color based on type
        const backgroundClasses = {
            'success': 'bg-green-500 text-white',
            'error': 'bg-red-500 text-white',
            'warning': 'bg-yellow-500 text-white',
            'info': 'bg-blue-500 text-white'
        };
        
        const iconClasses = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        toast.className += ` ${backgroundClasses[type] || backgroundClasses.info}`;
        
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="mr-3">
                    <i class="${iconClasses[type] || iconClasses.info}"></i>
                </div>
                <div class="flex-1">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

console.log('API Client initialized successfully');