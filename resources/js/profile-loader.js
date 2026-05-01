const ProfileAPI = {
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

        if (this.user.user_type === 'admin') {
            window.location.href = '/dashboard';
            return;
        }

        this.renderNavbar();
        this.filterSidebarByRole();
        this.registerEvents();

        await this.loadProfile();
    },

    registerEvents() {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await this.saveProfile();
            });
        }

        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await this.changePassword();
            });
        }

        const openVerifyModalButton = document.getElementById('openVerifyEmailModalBtn');
        if (openVerifyModalButton) {
            openVerifyModalButton.addEventListener('click', async () => {
                await this.openVerifyEmailModal();
            });
        }

        const submitVerifyButton = document.getElementById('submitVerifyEmailModalBtn');
        if (submitVerifyButton) {
            submitVerifyButton.addEventListener('click', async () => {
                await this.submitVerifyEmailCode();
            });
        }

        const modalCodeInput = document.getElementById('verifyEmailModalCode');
        if (modalCodeInput) {
            modalCodeInput.addEventListener('keydown', async (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    await this.submitVerifyEmailCode();
                }
            });
        }

        const closeVerifyModalButtons = [
            document.getElementById('closeVerifyEmailModalBtn'),
            document.getElementById('closeVerifyEmailModalX'),
        ];
        closeVerifyModalButtons.forEach((button) => {
            if (!button) {
                return;
            }

            button.addEventListener('click', () => {
                this.closeVerifyEmailModal();
            });
        });

        const verifyModal = document.getElementById('verifyEmailModal');
        if (verifyModal) {
            verifyModal.addEventListener('click', (event) => {
                if (event.target === verifyModal) {
                    this.closeVerifyEmailModal();
                }
            });
        }

        const photoInput = document.getElementById('profilePhotoInput');
        if (photoInput) {
            photoInput.addEventListener('change', async (event) => {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }

                await this.uploadPhoto(file);
                event.target.value = '';
            });
        }
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

    async loadProfile() {
        const data = await this.request('/api/profile');
        if (!data.success) {
            throw new Error(data.message || 'Failed to load profile');
        }

        this.applyProfileData(data.user, data.progress, data.pendingProfileRequest);
    },

    applyProfileData(user, progress, pendingRequest) {
        this.user = {
            ...this.user,
            ...user,
        };
        localStorage.setItem('user', JSON.stringify(this.user));

        this.renderNavbar();
        this.renderAvatar(user);
        this.renderProfileSummary(user);
        this.fillProfileForm(user);
        this.renderVerificationBadge(user, progress);
        this.renderVerificationActions(user, progress);
        this.renderProgress(progress);
        this.renderPendingRequest(pendingRequest);
        this.applyRoleFieldRules(user.user_type);
    },

    isEmailVerified(user, progress) {
        const fromUser = Boolean(user?.email_verified || user?.email_verified_at);
        if (fromUser) {
            return true;
        }

        const progressItems = Array.isArray(progress?.items) ? progress.items : [];
        const verificationItem = progressItems.find((item) => item?.key === 'email_verification');

        return Boolean(verificationItem?.done);
    },

    applyRoleFieldRules(role) {
        const professorGroup = document.getElementById('professorReadonlyFields');
        const studentGroup = document.getElementById('studentReadonlyFields');

        const isProfessor = role === 'professor';
        const isStudent = role === 'student';

        if (professorGroup) {
            professorGroup.classList.toggle('hidden', !isProfessor);
        }
        if (studentGroup) {
            studentGroup.classList.toggle('hidden', !isStudent);
        }
    },

    renderAvatar(user) {
        const avatar = document.getElementById('profileAvatar');
        if (!avatar) {
            return;
        }

        if (user.profile_photo_url) {
            avatar.style.backgroundImage = `url('${user.profile_photo_url}')`;
            avatar.style.backgroundSize = 'cover';
            avatar.style.backgroundPosition = 'center';
            avatar.textContent = '';
            avatar.classList.add('photo');
            return;
        }

        avatar.classList.remove('photo');
        avatar.style.backgroundImage = '';
        avatar.textContent = this.getInitials(user.name);
    },

    renderProfileSummary(user) {
        const displayName = document.getElementById('profileDisplayName');
        const roleLine = document.getElementById('profileRoleLine');
        const codeLine = document.getElementById('profileCodeLine');

        if (displayName) {
            displayName.textContent = user.name || 'User';
        }
        if (roleLine) {
            roleLine.textContent = `${(user.user_type || '').toUpperCase()} Account`;
        }
        if (codeLine) {
            codeLine.textContent = user.user_code || 'No ID';
        }
    },

    fillProfileForm(user) {
        this.setValue('profileName', user.name || '');
        this.setValue('profileEmail', user.email || '');
        this.setValue('profileBirthday', user.birthday || '');
        this.setValue('profileContact', user.contact || '');
        this.setValue('profileDesignation', user.designation || '');
        this.setValue('profileDepartment', user.department || '');
        this.setValue('profileCourse', user.course || '');
        this.setValue('profileYearLevel', user.year_level || '');
    },

    renderVerificationBadge(user, progress) {
        const badge = document.getElementById('verificationBadge');
        if (!badge) {
            return;
        }

        const isVerified = this.isEmailVerified(user, progress);

        if (isVerified) {
            badge.className = 'verification-badge verified';
            badge.textContent = 'Verified ✓';
        } else {
            badge.className = 'verification-badge pending';
            badge.textContent = 'Not Verified';
        }
    },

    renderVerificationActions(user, progress) {
        const actions = document.getElementById('verificationActions');
        const verifiedStatus = document.getElementById('verifiedStatus');
        const openVerifyButton = document.getElementById('openVerifyEmailModalBtn');

        const isVerified = this.isEmailVerified(user, progress);

        if (actions) {
            actions.classList.toggle('hidden', isVerified);
        }
        if (verifiedStatus) {
            verifiedStatus.classList.toggle('hidden', !isVerified);
        }
        if (openVerifyButton) {
            openVerifyButton.disabled = isVerified;
        }

        if (isVerified) {
            this.closeVerifyEmailModal();
        }
    },

    renderProgress(progress) {
        const bar = document.getElementById('profileProgressBar');
        const label = document.getElementById('profileProgressLabel');
        const meta = document.getElementById('profileProgressMeta');

        if (!progress) {
            return;
        }

        if (bar) {
            bar.style.width = `${progress.percent || 0}%`;
        }
        if (label) {
            label.textContent = `${progress.percent || 0}%`;
        }
        if (meta) {
            meta.textContent = `${progress.completed || 0} / ${progress.total || 0} completed`;
        }
    },

    renderPendingRequest(pendingRequest) {
        const text = document.getElementById('pendingRequestText');
        if (!text) {
            return;
        }

        if (!pendingRequest) {
            text.textContent = 'No pending profile field update request.';
            return;
        }

        const keys = Object.keys(pendingRequest.requested_changes || {});
        if (!keys.length) {
            text.textContent = 'Pending admin review.';
            return;
        }

        text.textContent = `Pending admin approval for: ${keys.join(', ')}`;
    },

    async saveProfile() {
        const payload = {
            name: this.getValue('profileName'),
            email: this.getValue('profileEmail'),
            birthday: this.getValue('profileBirthday') || null,
            contact: this.getValue('profileContact') || null,
        };

        const data = await this.request('/api/profile', {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to save profile');
        }

        this.applyProfileData(data.user, data.progress, data.pendingProfileRequest);
        this.showMessage(data.message || 'Profile updated successfully.', 'success');
        this.refreshNotificationCounter();
    },

    async changePassword() {
        const payload = {
            current_password: this.getValue('currentPassword'),
            new_password: this.getValue('newPassword'),
            new_password_confirmation: this.getValue('newPasswordConfirmation'),
        };

        const data = await this.request('/api/profile/password', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to update password');
        }

        this.setValue('currentPassword', '');
        this.setValue('newPassword', '');
        this.setValue('newPasswordConfirmation', '');

        this.showMessage(data.message || 'Password updated.', 'success');
        this.refreshNotificationCounter();
    },

    async sendVerificationCode(options = {}) {
        const { showSuccessMessage = true } = options;

        const data = await this.request('/api/profile/email-verification/send', {
            method: 'POST',
            body: JSON.stringify({}),
        });

        if (!data.success) {
            throw new Error(data.message || 'Failed to send verification code');
        }

        if (showSuccessMessage) {
            this.showMessage(data.message || 'Verification code sent.', 'success');
        }

        this.refreshNotificationCounter();

        return data;
    },

    async openVerifyEmailModal() {
        const isVerified = this.isEmailVerified(this.user, null);
        if (isVerified) {
            this.showMessage('Email is already verified.', 'success');
            return;
        }

        try {
            const data = await this.sendVerificationCode({ showSuccessMessage: false });
            const remainingAttempts = Number(data?.remaining_attempts ?? 3);
            this.updateVerifyAttemptInfo(
                `A 6-digit code was sent. ${remainingAttempts} attempt(s) remaining.`,
                'info'
            );

            const modal = document.getElementById('verifyEmailModal');
            if (modal) {
                modal.classList.remove('hidden');
            }

            this.setValue('verifyEmailModalCode', '');

            const codeInput = document.getElementById('verifyEmailModalCode');
            if (codeInput) {
                codeInput.focus();
            }

            this.showMessage('Verification code sent to your email.', 'success');
        } catch (error) {
            this.showVerificationError(error);
        }
    },

    closeVerifyEmailModal() {
        const modal = document.getElementById('verifyEmailModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    },

    async submitVerifyEmailCode() {
        const code = this.getValue('verifyEmailModalCode').trim();
        if (!/^\d{6}$/.test(code)) {
            this.updateVerifyAttemptInfo('Please enter a valid 6-digit code.', 'error');
            return;
        }

        try {
            const data = await this.request('/api/profile/email-verification/verify', {
                method: 'POST',
                body: JSON.stringify({ code }),
            });

            if (!data.success) {
                throw new Error(data.message || 'Failed to verify email');
            }

            this.closeVerifyEmailModal();
            this.setValue('verifyEmailModalCode', '');
            await this.loadProfile();
            this.showMessage(data.message || 'Email verified successfully.', 'success');
            this.refreshNotificationCounter();
        } catch (error) {
            this.showVerificationError(error);
        }
    },

    showVerificationError(error) {
        const payload = error?.payload || {};

        if (error?.status === 429) {
            const retry = Number(payload.retry_after_seconds || 10);
            this.updateVerifyAttemptInfo(`Too many attempts. Please wait ${retry}s before trying again.`, 'error');
            this.showMessage(error.message || 'Too many attempts.', 'error');
            return;
        }

        if (error?.status === 422 && typeof payload.remaining_attempts === 'number') {
            this.updateVerifyAttemptInfo(`Invalid code. ${payload.remaining_attempts} attempt(s) remaining.`, 'error');
            this.showMessage(error.message || 'Invalid verification code.', 'error');
            return;
        }

        this.updateVerifyAttemptInfo(error?.message || 'Verification failed.', 'error');
        this.showMessage(error?.message || 'Verification failed.', 'error');
    },

    updateVerifyAttemptInfo(message, variant = 'info') {
        const info = document.getElementById('verifyAttemptInfo');
        if (!info) {
            return;
        }

        info.textContent = message;
        info.classList.remove('error', 'success');
        if (variant === 'error') {
            info.classList.add('error');
        }
        if (variant === 'success') {
            info.classList.add('success');
        }
    },

    async uploadPhoto(file) {
        const token = localStorage.getItem('api_token');
        const formData = new FormData();
        formData.append('photo', file);

        const response = await fetch('/api/profile/photo', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: formData,
        });

        let data = {};
        try {
            data = await response.json();
        } catch (_error) {
            data = {};
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to upload photo');
        }

        this.applyProfileData(data.user, data.progress, null);
        this.showMessage(data.message || 'Photo uploaded.', 'success');
        this.refreshNotificationCounter();
    },

    refreshNotificationCounter() {
        if (typeof window.refreshNotificationCounter === 'function') {
            window.refreshNotificationCounter();
        }
    },

    showMessage(message, type = 'success') {
        const box = document.getElementById('profileMessage');
        if (!box) {
            return;
        }

        box.className = type === 'success' ? 'success-message-box' : 'error-message-box';
        box.textContent = message;

        setTimeout(() => {
            box.className = 'hidden';
            box.textContent = '';
        }, 4500);
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
            const error = new Error(data.message || 'Request failed');
            error.status = response.status;
            error.payload = data;
            throw error;
        }

        return data;
    },

    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value;
        }
    },

    getValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value : '';
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

window.logout = logout;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await ProfileAPI.init();
    } catch (error) {
        ProfileAPI.showMessage(error.message || 'Unable to load profile page', 'error');
    }
});
