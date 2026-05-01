const NotificationsAPI = {
    user: null,

    async init() {
        const userJSON = localStorage.getItem('user');
        if (!userJSON) {
            window.location.href = '/';
            return;
        }

        this.user = JSON.parse(userJSON);
        if (this.user.user_type === 'user') {
            this.user.user_type = 'student';
            localStorage.setItem('user', JSON.stringify(this.user));
        }

        this.renderNavbar();
        this.filterSidebarByRole();
        await this.loadNotifications();
    },

    renderNavbar() {
        const userName = document.getElementById('userName');
        const userInitials = document.getElementById('userInitials');
        const navbar = document.getElementById('navbar');

        if (userName) {
            userName.textContent = this.user.name;
        }

        if (userInitials) {
            userInitials.className = `user-badge ${this.user.user_type}`;

            if (this.user.profile_photo_url) {
                userInitials.style.backgroundImage = `url('${this.user.profile_photo_url}')`;
                userInitials.classList.add('photo');
                userInitials.textContent = '';
            } else {
                userInitials.style.backgroundImage = '';
                userInitials.classList.remove('photo');
                userInitials.textContent = this.getInitials(this.user.name);
            }
        }

        if (navbar && this.user.user_type === 'admin') {
            navbar.classList.add('admin-navbar');
        }
    },

    filterSidebarByRole() {
        const role = this.user.user_type;
        document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item').forEach((item) => {
            const allowedRoles = item.getAttribute('data-roles');
            if (!allowedRoles) {
                return;
            }

            const roles = allowedRoles.split(',').map((value) => value.trim());
            item.style.display = roles.includes(role) ? 'flex' : 'none';
        });
    },

    async loadNotifications() {
        const data = await this.request('/api/notifications');

        if (!data.success) {
            throw new Error(data.message || 'Failed to load notifications');
        }

        this.renderUnreadCount(data.unreadCount || 0);
        this.renderNotificationsList(data.notifications || []);
        this.renderAdminApprovals(data.pendingProfileRequests || []);
        this.refreshNotificationCounter();
    },

    renderUnreadCount(count) {
        const element = document.getElementById('unreadCount');
        if (element) {
            element.textContent = String(count);
        }
    },

    renderNotificationsList(notifications) {
        const list = document.getElementById('notificationsList');
        if (!list) {
            return;
        }

        if (!notifications.length) {
            list.innerHTML = '<div class="notification-item">No notifications yet.</div>';
            return;
        }

        list.innerHTML = notifications.map((item) => {
            const unreadClass = item.is_read ? '' : 'unread';
            const readButton = item.is_read
                ? ''
                : `<button class="action-btn" onclick="markNotificationRead(${item.id})">Mark as read</button>`;

            return `
                <div class="notification-item ${unreadClass}">
                    <div class="notification-head">
                        <strong>${this.escapeHTML(item.title || 'Notification')}</strong>
                        <span>${new Date(item.created_at).toLocaleString()}</span>
                    </div>
                    <p>${this.escapeHTML(item.message || '')}</p>
                    <div class="notification-actions">${readButton}</div>
                </div>
            `;
        }).join('');
    },

    renderAdminApprovals(requests) {
        const card = document.getElementById('adminApprovalsCard');
        const list = document.getElementById('pendingApprovalsList');

        if (!card || !list) {
            return;
        }

        card.classList.remove('hidden');

        if (this.user.user_type !== 'admin') {
            list.innerHTML = '<div class="notification-item">Pending approvals are available to admins only.</div>';
            return;
        }

        if (!requests.length) {
            list.innerHTML = '<div class="notification-item">No pending profile approval requests.</div>';
            return;
        }

        list.innerHTML = requests.map((request) => {
            const fields = Object.keys(request.requested_changes || {});
            const formatted = fields.map((field) => `${field}: ${request.requested_changes[field] ?? ''}`).join(', ');

            return `
                <div class="notification-item unread">
                    <div class="notification-head">
                        <strong>${this.escapeHTML(request.user_name || 'User')}</strong>
                        <span>${this.escapeHTML(request.user_code || 'No ID')}</span>
                    </div>
                    <p>Requested changes: ${this.escapeHTML(formatted || 'N/A')}</p>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="approveProfileRequest(${request.id})">Approve</button>
                        <button class="action-btn delete" onclick="rejectProfileRequest(${request.id})">Reject</button>
                    </div>
                </div>
            `;
        }).join('');
    },

    async markNotificationRead(id) {
        const data = await this.request(`/api/notifications/${id}/read`, {
            method: 'POST',
            body: JSON.stringify({}),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to mark notification read');
        }

        await this.loadNotifications();
    },

    async approveProfileRequest(id) {
        const data = await this.request(`/api/profile-change-requests/${id}/approve`, {
            method: 'POST',
            body: JSON.stringify({}),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to approve request');
        }

        await this.loadNotifications();
    },

    async rejectProfileRequest(id) {
        const note = prompt('Optional rejection note:', '') || '';

        const data = await this.request(`/api/profile-change-requests/${id}/reject`, {
            method: 'POST',
            body: JSON.stringify({ note: note.trim() || null }),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to reject request');
        }

        await this.loadNotifications();
    },

    refreshNotificationCounter() {
        if (typeof window.refreshNotificationCounter === 'function') {
            window.refreshNotificationCounter();
        }
    },

    async request(url, options = {}) {
        const token = localStorage.getItem('api_token');

        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
                ...(options.headers || {}),
            },
        });

        let data = {};
        try {
            data = await response.json();
        } catch (_error) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    },

    getInitials(name) {
        const parts = String(name || '').trim().split(' ').filter(Boolean);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        if (parts.length === 1) {
            return parts[0][0].toUpperCase();
        }
        return 'U';
    },

    escapeHTML(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
};

async function markNotificationRead(id) {
    await NotificationsAPI.markNotificationRead(id);
}

async function approveProfileRequest(id) {
    await NotificationsAPI.approveProfileRequest(id);
}

async function rejectProfileRequest(id) {
    await NotificationsAPI.rejectProfileRequest(id);
}

function logout() {
    const token = localStorage.getItem('api_token');

    if (!token) {
        window.location.href = '/login';
        return;
    }

    fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
        },
    })
        .finally(() => {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        });
}

window.markNotificationRead = markNotificationRead;
window.approveProfileRequest = approveProfileRequest;
window.rejectProfileRequest = rejectProfileRequest;
window.logout = logout;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await NotificationsAPI.init();
    } catch (error) {
        alert(error.message || 'Unable to load notifications page');
    }
});
