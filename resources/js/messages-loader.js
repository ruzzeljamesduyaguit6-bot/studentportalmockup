const MessageAPI = {
    user: null,
    users: [],
    conversationUsers: [],
    filteredUsers: [],
    activeTab: 'global',
    selectedPrivateUser: null,
    refreshTimer: null,
    searchRequestId: 0,

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

        await this.loadBootstrap();
        await this.loadGlobalMessages(true);
        this.switchTab('global');

        this.startAutoRefresh();
    },

    renderNavbar() {
        const userName = document.getElementById('userName');
        const userInitials = document.getElementById('userInitials');
        const navbar = document.getElementById('navbar');
        const navbarTitle = document.getElementById('navbarTitle');

        userName.textContent = this.user.name;

        const nameParts = this.user.name.trim().split(' ');
        const initials = nameParts.length >= 2
            ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
            : nameParts[0].charAt(0).toUpperCase();

        userInitials.className = `user-badge ${this.user.user_type}`;

        if (this.user.profile_photo_url) {
            userInitials.style.backgroundImage = `url('${this.user.profile_photo_url}')`;
            userInitials.classList.add('photo');
            userInitials.textContent = '';
        } else {
            userInitials.style.backgroundImage = '';
            userInitials.classList.remove('photo');
            userInitials.textContent = initials;
        }

        const roleTitles = {
            admin: 'Admin Dashboard',
            student: 'Student Portal',
            professor: 'Professor Portal'
        };

        const navbarTitleText = document.getElementById('navbarTitleText');
        const titleTarget = navbarTitleText || navbarTitle;
        titleTarget.textContent = 'Messages';

        if (this.user.user_type === 'admin') {
            navbar.classList.add('admin-navbar');
        } else {
            navbar.classList.remove('admin-navbar');
        }

        document.title = `${roleTitles[this.user.user_type] || 'Dashboard'} - Messages`;
    },

    filterSidebarByRole() {
        const userRole = this.user.user_type;
        const navItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item');

        navItems.forEach(item => {
            const allowedRoles = item.getAttribute('data-roles');
            if (!allowedRoles) {
                return;
            }

            const roles = allowedRoles.split(',').map(role => role.trim());
            item.style.display = roles.includes(userRole) ? 'flex' : 'none';
        });
    },

    async loadBootstrap() {
        const data = await this.request('/api/messages/bootstrap');
        if (!data.success) {
            throw new Error(data.message || 'Failed to load messaging users');
        }

        this.user = {
            ...this.user,
            ...(data.currentUser || {}),
        };
        localStorage.setItem('user', JSON.stringify(this.user));
        this.users = data.users || [];
        this.conversationUsers = [...this.users];
        this.filteredUsers = [...this.conversationUsers];
        this.renderPrivateUserList();
    },

    async loadGlobalMessages(force = false) {
        if (!force && this.activeTab !== 'global') {
            return;
        }

        const data = await this.request('/api/messages/global');
        if (!data.success) {
            throw new Error(data.message || 'Failed to load global messages');
        }

        this.renderMessages(document.getElementById('globalMessagesList'), data.messages || []);
    },

    async loadPrivateMessages(force = false) {
        if (!force && this.activeTab !== 'private') {
            return;
        }

        if (!this.selectedPrivateUser) {
            return;
        }

        const data = await this.request(`/api/messages/private/${this.selectedPrivateUser.id}`);
        if (!data.success) {
            throw new Error(data.message || 'Failed to load private messages');
        }

        this.renderMessages(document.getElementById('privateMessagesList'), data.messages || []);
    },

    async sendGlobalMessage() {
        const input = document.getElementById('globalMessageInput');
        const body = input.value.trim();
        if (!body) {
            return;
        }

        await this.request('/api/messages/global', {
            method: 'POST',
            body: JSON.stringify({ body }),
        });

        input.value = '';
        await this.loadGlobalMessages();
    },

    async sendPrivateMessage() {
        if (!this.selectedPrivateUser) {
            return;
        }

        const input = document.getElementById('privateMessageInput');
        const body = input.value.trim();
        if (!body) {
            return;
        }

        await this.request(`/api/messages/private/${this.selectedPrivateUser.id}`, {
            method: 'POST',
            body: JSON.stringify({ body }),
        });

        input.value = '';
        await this.loadPrivateMessages();
    },

    async toggleReaction(messageId, emoji) {
        await this.request(`/api/messages/${messageId}/react`, {
            method: 'POST',
            body: JSON.stringify({ emoji }),
        });

        if (this.activeTab === 'global') {
            await this.loadGlobalMessages();
        } else {
            await this.loadPrivateMessages();
        }
    },

    renderMessages(container, messages) {
        if (!container) {
            return;
        }

        if (!messages.length) {
            container.innerHTML = '<div class="message-empty">No messages yet.</div>';
            return;
        }

        container.innerHTML = messages.map(message => {
            const isOwn = message.sender_id === this.user.id;
            const reactions = this.groupReactions(message.reactions || []);
            const senderName = this.escapeHTML(message.sender_name || 'Unknown');
            const senderInitials = this.escapeHTML(this.getInitials(message.sender_name || 'U'));
            const senderPhotoUrl = this.escapeHTML(message.sender_profile_photo_url || '');
            const senderVerifiedBadge = message.sender_email_verified_at
                ? '<span class="verified-mini-badge" title="Email verified">✓</span>'
                : '';
            const senderIconClass = senderPhotoUrl ? 'message-sender-icon photo' : 'message-sender-icon';
            const senderIconStyle = senderPhotoUrl ? ` style="background-image:url('${senderPhotoUrl}')"` : '';
            const senderIconText = senderPhotoUrl ? '' : senderInitials;

            return `
                <div class="message-item ${isOwn ? 'own' : ''}">
                    <div class="message-meta">
                        <span class="message-sender">
                            <span class="${senderIconClass}"${senderIconStyle}>${senderIconText}</span>
                            <strong>${senderName}${senderVerifiedBadge}</strong>
                        </span>
                        <span>${new Date(message.created_at).toLocaleString()}</span>
                    </div>
                    <div class="message-body">${this.escapeHTML(message.body)}</div>
                    <div class="message-reactions">
                        ${reactions.map(reaction => `
                            <button class="reaction-chip ${reaction.mine ? 'mine' : ''}" onclick="toggleReaction(${message.id}, '${reaction.emoji}')">${reaction.emoji} ${reaction.count}</button>
                        `).join('')}
                        <button class="reaction-add" onclick="openReactionPicker(${message.id})">+</button>
                    </div>
                </div>
            `;
        }).join('');

        container.scrollTop = container.scrollHeight;
    },

    groupReactions(reactions) {
        const grouped = {};

        reactions.forEach(reaction => {
            if (!grouped[reaction.emoji]) {
                grouped[reaction.emoji] = {
                    emoji: reaction.emoji,
                    count: 0,
                    mine: false,
                };
            }

            grouped[reaction.emoji].count += 1;
            if (reaction.user_id === this.user.id) {
                grouped[reaction.emoji].mine = true;
            }
        });

        return Object.values(grouped);
    },

    renderPrivateUserList() {
        const list = document.getElementById('privateUsersList');
        if (!list) {
            return;
        }

        if (!this.filteredUsers.length) {
            list.innerHTML = '<div class="message-empty">No users found.</div>';
            return;
        }

        list.innerHTML = this.filteredUsers.map(user => {
            const selected = this.selectedPrivateUser && this.selectedPrivateUser.id === user.id ? 'active' : '';
            const initials = this.escapeHTML(this.getInitials(user.name));
            const photoUrl = this.escapeHTML(user.profile_photo_url || '');
            const iconClass = photoUrl ? 'private-user-icon photo' : 'private-user-icon';
            const iconStyle = photoUrl ? ` style="background-image:url('${photoUrl}')"` : '';
            const iconText = photoUrl ? '' : initials;
            const verifiedBadge = user.email_verified_at
                ? '<span class="verified-mini-badge" title="Email verified">✓</span>'
                : '';
            return `
                <button class="private-user-item ${selected}" onclick="selectPrivateUser(${user.id})">
                    <div class="private-user-name-row">
                        <span class="${iconClass}"${iconStyle}>${iconText}</span>
                        <div class="private-user-name">${this.escapeHTML(user.name)}${verifiedBadge}</div>
                    </div>
                    <div class="private-user-code">${this.escapeHTML(user.user_code || 'No ID')}</div>
                </button>
            `;
        }).join('');
    },

    setPrivateUser(userId) {
        const selected = this.users.find(user => user.id === userId)
            || this.filteredUsers.find(user => user.id === userId)
            || this.conversationUsers.find(user => user.id === userId);
        if (!selected) {
            return;
        }

        this.selectedPrivateUser = selected;
        this.renderPrivateUserList();

        const header = document.getElementById('privateChatHeader');
        const form = document.getElementById('privateMessageForm');
        if (header) {
            header.textContent = `Chat with ${selected.name}`;
        }
        if (form) {
            form.classList.remove('hidden');
        }
    },

    async filterUsers(query) {
        const term = query.trim().toLowerCase();
        if (!term) {
            this.filteredUsers = [...this.conversationUsers];
            this.renderPrivateUserList();
            return;
        }

        const requestId = ++this.searchRequestId;
        try {
            const data = await this.request(`/api/messages/users/search?query=${encodeURIComponent(term)}`);
            if (requestId !== this.searchRequestId) {
                return;
            }
            this.filteredUsers = data.users || [];
        } catch (_error) {
            this.filteredUsers = [];
        }

        this.renderPrivateUserList();
    },

    switchTab(tab) {
        if (tab !== 'global' && tab !== 'private') {
            return;
        }

        this.activeTab = tab;

        const globalTab = document.getElementById('globalTab');
        const privateTab = document.getElementById('privateTab');
        const globalPanel = document.getElementById('globalPanel');
        const privatePanel = document.getElementById('privatePanel');
        const privateMessagesList = document.getElementById('privateMessagesList');

        if (tab === 'global') {
            globalTab.classList.add('active');
            privateTab.classList.remove('active');
            globalPanel.classList.remove('hidden');
            globalPanel.style.display = 'flex';
            privatePanel.classList.add('hidden');
            privatePanel.style.display = 'none';
            this.loadGlobalMessages();
        } else {
            privateTab.classList.add('active');
            globalTab.classList.remove('active');
            privatePanel.classList.remove('hidden');
            privatePanel.style.display = 'flex';
            globalPanel.classList.add('hidden');
            globalPanel.style.display = 'none';
            this.filterUsers(document.getElementById('privateSearchInput')?.value || '');
            if (this.selectedPrivateUser) {
                this.loadPrivateMessages();
            } else if (privateMessagesList) {
                privateMessagesList.innerHTML = '<div class="message-empty">Select a user to start messaging.</div>';
            }
        }
    },

    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.refreshTimer = setInterval(() => {
            if (this.activeTab === 'global') {
                this.loadGlobalMessages();
            } else if (this.selectedPrivateUser) {
                this.loadPrivateMessages();
            }
        }, 8000);
    },

    async request(url, options = {}) {
        const token = localStorage.getItem('api_token');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...(options.headers || {}),
        };

        const response = await fetch(url, {
            ...options,
            headers,
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

    escapeHTML(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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
    }
};

function switchMessageTab(tab) {
    MessageAPI.switchTab(tab);
}

function filterPrivateUsers() {
    const input = document.getElementById('privateSearchInput');
    MessageAPI.filterUsers(input ? input.value : '');
}

async function selectPrivateUser(userId) {
    MessageAPI.setPrivateUser(userId);
    await MessageAPI.loadPrivateMessages();

    const alreadyInConversationList = MessageAPI.conversationUsers.some(user => user.id === userId);
    if (!alreadyInConversationList) {
        const selected = MessageAPI.users.find(user => user.id === userId)
            || MessageAPI.filteredUsers.find(user => user.id === userId);
        if (selected) {
            MessageAPI.conversationUsers.unshift(selected);
            MessageAPI.users = [...MessageAPI.conversationUsers];
        }
    }
}

async function toggleReaction(messageId, emoji) {
    await MessageAPI.toggleReaction(messageId, emoji);
}

async function openReactionPicker(messageId) {
    const emoji = prompt('React with emoji (example: 👍, ❤️, 😂):', '👍');
    if (!emoji) {
        return;
    }

    await MessageAPI.toggleReaction(messageId, emoji.trim());
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
            'Authorization': `Bearer ${token}`
        }
    })
    .then(() => {
        localStorage.removeItem('api_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    })
    .catch(() => {
        localStorage.removeItem('api_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    });
}

window.switchMessageTab = switchMessageTab;
window.filterPrivateUsers = filterPrivateUsers;
window.selectPrivateUser = selectPrivateUser;
window.toggleReaction = toggleReaction;
window.openReactionPicker = openReactionPicker;
window.logout = logout;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await MessageAPI.init();

        const globalForm = document.getElementById('globalMessageForm');
        if (globalForm) {
            globalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await MessageAPI.sendGlobalMessage();
            });
        }

        const privateForm = document.getElementById('privateMessageForm');
        if (privateForm) {
            privateForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await MessageAPI.sendPrivateMessage();

                if (MessageAPI.selectedPrivateUser) {
                    const selectedId = MessageAPI.selectedPrivateUser.id;
                    const exists = MessageAPI.conversationUsers.some(user => user.id === selectedId);
                    if (!exists) {
                        MessageAPI.conversationUsers.unshift(MessageAPI.selectedPrivateUser);
                    }
                    const searchInput = document.getElementById('privateSearchInput');
                    const currentTerm = searchInput ? searchInput.value.trim() : '';
                    if (!currentTerm) {
                        MessageAPI.filteredUsers = [...MessageAPI.conversationUsers];
                        MessageAPI.renderPrivateUserList();
                    }
                }
            });
        }
    } catch (error) {
        alert(error.message || 'Failed to load messages page');
    }
});
