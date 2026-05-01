import './bootstrap';

const NotificationCounter = {
	timerId: null,

	async refresh() {
		const token = localStorage.getItem('api_token');
		const user = localStorage.getItem('user');

		if (!token || !user) {
			this.render(0);
			return;
		}

		const hasNotificationsNav = document.querySelector('.sidebar-nav .nav-item[href="/notifications"]');
		if (!hasNotificationsNav) {
			return;
		}

		try {
			const response = await fetch('/api/notifications', {
				method: 'GET',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'Authorization': `Bearer ${token}`,
				},
			});

			if (!response.ok) {
				return;
			}

			const data = await response.json();
			this.render(Number(data.unreadCount || 0));
		} catch (_error) {
			// Keep silent to avoid noisy UI on transient failures.
		}
	},

	render(count) {
		const links = document.querySelectorAll('.sidebar-nav .nav-item[href="/notifications"]');
		links.forEach((link) => {
			let badge = link.querySelector('.nav-notification-counter');
			if (!badge) {
				badge = document.createElement('span');
				badge.className = 'nav-notification-counter hidden';
				link.appendChild(badge);
			}

			if (count > 0) {
				badge.textContent = count > 99 ? '99+' : String(count);
				badge.classList.remove('hidden');
			} else {
				badge.textContent = '';
				badge.classList.add('hidden');
			}
		});
	},

	start() {
		if (this.timerId) {
			clearInterval(this.timerId);
		}

		this.refresh();
		this.timerId = window.setInterval(() => {
			this.refresh();
		}, 30000);
	},
};

window.refreshNotificationCounter = () => NotificationCounter.refresh();

document.addEventListener('DOMContentLoaded', () => {
	NotificationCounter.start();
});
