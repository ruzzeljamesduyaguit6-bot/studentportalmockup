/**
 * Dashboard Loader - Fetches dashboard data via API and renders dashboard UI
 * No DOM manipulation or visible code - pure API consumption
 */

(function() {
    'use strict';

    // Get token from localStorage
    const token = localStorage.getItem('authToken');
    
    // Redirect to login if not authenticated
    if (!token) {
        window.location.href = '/login';
        return;
    }

    /**
     * Dashboard namespace - handles all dashboard operations
     */
    const Dashboard = {
        token: token,
        data: null,
        isAdmin: false,

        /**
         * Initialize dashboard - fetch data and render
         */
        init: async function() {
            try {
                // Fetch dashboard data from API
                await this.fetchDashboardData();
                
                // Render all dashboard components
                this.render();
            } catch (error) {
                console.error('Dashboard initialization error:', error);
                this.renderError('Failed to load dashboard. Please refresh the page.');
            }
        },

        /**
         * Fetch dashboard data from API endpoint
         */
        fetchDashboardData: async function() {
            const response = await fetch('/api/dashboard/data', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    localStorage.removeItem('authToken');
                    window.location.href = '/login';
                    return;
                }
                throw new Error(`API responded with status ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to fetch dashboard data');
            }

            this.data = result;
            this.isAdmin = result.isAdmin;
            
            // Store user data in localStorage for components
            localStorage.setItem('userData', JSON.stringify(result.user));
        },

        /**
         * Master render function - orchestrates all dashboard rendering
         */
        render: function() {
            if (!this.data) {
                this.renderError('No dashboard data available');
                return;
            }

            const root = document.getElementById('dashboard-root');
            if (!root) {
                console.error('Dashboard root element not found');
                return;
            }

            // Build dashboard HTML
            let html = `
                <div class="dashboard-container ${this.data.config.navbarClass}">
                    <!-- Navbar -->
                    <nav class="navbar">
                        <div class="navbar-content">
                            <div class="navbar-header">
                                <span class="navbar-icon">${this.data.config.navbarIcon}</span>
                                <h1 class="navbar-title">${this.data.config.navbarTitle}</h1>
                            </div>
                            <button onclick="Dashboard.logout()" class="logout-btn">Logout</button>
                        </div>
                    </nav>

                    <!-- Main Content -->
                    <div class="dashboard-content">
                        <!-- Welcome Section -->
                        <div class="welcome-section">
                            <h2>${this.data.config.welcomeTitle}</h2>
                            <p>${this.data.config.welcomeText}</p>
                            <p class="user-info">Logged in as: <strong>${this.data.user.name}</strong> (${this.data.user.email})</p>
                        </div>

                        <!-- Stats Section (Admin Only) -->
                        ${this.data.config.showStats ? this.renderStats() : ''}

                        <!-- Actions Section -->
                        <div class="actions-section">
                            <h3>Available Actions</h3>
                            <div class="actions-grid">
                                ${this.renderActions()}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            root.innerHTML = html;

            // Add inline styles
            this.injectStyles();
        },

        /**
         * Render stats section (admin only)
         */
        renderStats: function() {
            if (!this.data.config.stats || this.data.config.stats.length === 0) {
                return '';
            }

            let statsHtml = '<div class="stats-section"><div class="stats-grid">';
            
            this.data.config.stats.forEach(stat => {
                statsHtml += `
                    <div class="stat-card">
                        <div class="stat-value">${stat.value}</div>
                        <div class="stat-label">${stat.label}</div>
                    </div>
                `;
            });

            statsHtml += '</div></div>';
            return statsHtml;
        },

        /**
         * Render action cards based on user role
         */
        renderActions: function() {
            if (!this.data.actions || this.data.actions.length === 0) {
                return '<p>No actions available</p>';
            }

            let actionsHtml = '';
            
            this.data.actions.forEach((action, index) => {
                const cardClass = this.isAdmin ? 'admin-action' : 'user-action';
                actionsHtml += `
                    <div class="action-card ${cardClass}">
                        <div class="action-card-title">${action.title}</div>
                        <div class="action-card-description">${action.description}</div>
                        <button class="action-btn" onclick="Dashboard.handleAction('${action.title}')">
                            Access →
                        </button>
                    </div>
                `;
            });

            return actionsHtml;
        },

        /**
         * Handle action button clicks
         */
        handleAction: function(actionTitle) {
            console.log('Action clicked:', actionTitle);
            alert(`${actionTitle} feature coming soon!`);
        },

        /**
         * Logout user
         */
        logout: async function() {
            try {
                // Call logout API
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    console.log('Logout successful');
                }
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                // Clear authentication data
                localStorage.removeItem('authToken');
                localStorage.removeItem('userData');
                
                // Redirect to login
                window.location.href = '/login';
            }
        },

        /**
         * Render error message
         */
        renderError: function(message) {
            const root = document.getElementById('dashboard-root');
            if (!root) return;

            root.innerHTML = `
                <div style="padding: 20px; text-align: center; color: #d32f2f;">
                    <h2>Error</h2>
                    <p>${message}</p>
                    <button onclick="window.location.href='/login'" style="padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Back to Login
                    </button>
                </div>
            `;

            this.injectStyles();
        },

        /**
         * Inject CSS styles into the document
         */
        injectStyles: function() {
            // Check if styles already injected
            if (document.getElementById('dashboard-styles')) {
                return;
            }

            const styleSheet = document.createElement('style');
            styleSheet.id = 'dashboard-styles';
            styleSheet.textContent = `
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }

                .dashboard-container {
                    background: white;
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                }

                /* Navbar Styles */
                .navbar {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 20px 40px;
                    color: white;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }

                .navbar-container.admin-navbar {
                    background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
                }

                .navbar-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .navbar-header {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .navbar-icon {
                    font-size: 28px;
                }

                .navbar-title {
                    font-size: 24px;
                    font-weight: 600;
                }

                .logout-btn {
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                    border: 1px solid white;
                    padding: 10px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    transition: all 0.3s ease;
                }

                .logout-btn:hover {
                    background: rgba(255, 255, 255, 0.3);
                }

                /* Dashboard Content */
                .dashboard-content {
                    flex: 1;
                    padding: 40px;
                    max-width: 1200px;
                    margin: 0 auto;
                    width: 100%;
                }

                /* Welcome Section */
                .welcome-section {
                    margin-bottom: 40px;
                    padding: 30px;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    border-radius: 8px;
                }

                .welcome-section h2 {
                    color: #333;
                    margin-bottom: 10px;
                    font-size: 28px;
                }

                .welcome-section p {
                    color: #666;
                    line-height: 1.6;
                    margin-bottom: 10px;
                }

                .user-info {
                    font-size: 14px;
                    margin-top: 15px !important;
                    padding-top: 15px;
                    border-top: 1px solid rgba(0,0,0,0.1);
                }

                /* Stats Section */
                .stats-section {
                    margin-bottom: 40px;
                }

                .stats-section h3 {
                    color: #333;
                    margin-bottom: 20px;
                    font-size: 20px;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .stat-card {
                    background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
                    color: white;
                    padding: 25px;
                    border-radius: 8px;
                    text-align: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    transition: transform 0.3s ease;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                }

                .stat-value {
                    font-size: 32px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }

                .stat-label {
                    font-size: 14px;
                    opacity: 0.9;
                }

                /* Actions Section */
                .actions-section h3 {
                    color: #333;
                    margin-bottom: 20px;
                    font-size: 20px;
                }

                .actions-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 20px;
                }

                .action-card {
                    background: white;
                    border: 2px solid #e0e0e0;
                    padding: 25px;
                    border-radius: 8px;
                    transition: all 0.3s ease;
                    cursor: pointer;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                }

                .action-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                    border-color: #667eea;
                }

                .action-card.admin-action {
                    border-left: 4px solid #f44336;
                }

                .action-card.user-action {
                    border-left: 4px solid #667eea;
                }

                .action-card-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 10px;
                }

                .action-card-description {
                    font-size: 14px;
                    color: #666;
                    margin-bottom: 15px;
                    line-height: 1.5;
                }

                .action-btn {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    width: 100%;
                }

                .action-btn:hover {
                    transform: scale(1.02);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                /* Responsive Design */
                @media (max-width: 768px) {
                    .navbar {
                        padding: 15px 20px;
                    }

                    .navbar-content {
                        flex-direction: column;
                        gap: 15px;
                    }

                    .navbar-title {
                        font-size: 20px;
                    }

                    .dashboard-content {
                        padding: 20px;
                    }

                    .welcome-section {
                        padding: 20px;
                    }

                    .stats-grid,
                    .actions-grid {
                        grid-template-columns: 1fr;
                    }
                }
            `;

            document.head.appendChild(styleSheet);
        }
    };

    // Initialize dashboard when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Dashboard.init());
    } else {
        Dashboard.init();
    }

    // Expose Dashboard globally for button callbacks
    window.Dashboard = Dashboard;
})();
