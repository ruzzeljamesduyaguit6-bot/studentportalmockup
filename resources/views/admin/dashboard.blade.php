<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Dashboard - Role Based System</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js']" />
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;display:flex;flex-direction:column;min-height:100vh}
        .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:60px;background:#fff;border-bottom:1px solid #e0e0e0;box-shadow:0 2px 4px rgba(0,0,0,.06);position:sticky;top:0;z-index:100}
        .navbar-title{display:flex;align-items:center;gap:10px;font-size:1.1rem;font-weight:700;color:#333}
        .navbar-logo{height:36px;width:36px;object-fit:contain}
        .navbar-actions{display:flex;align-items:center;gap:12px}
        .user-info{display:flex;align-items:center;gap:8px;font-size:.9rem;color:#555}
        .user-badge{width:32px;height:32px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
        .logout-btn{padding:6px 14px;background:#e53e3e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.85rem}
        .page-wrapper{display:flex;flex:1}
        .sidebar{width:250px;background:linear-gradient(180deg,#f8f9fa 0%,#f0f1f3 100%);border-right:1px solid #ddd;padding:20px 0;overflow-y:auto;display:flex;flex-direction:column}
        .sidebar-brand{padding:0 20px 20px;font-weight:700;color:#333;font-size:1rem}
        .nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:.9rem;transition:background .2s}
        .nav-item:hover,.nav-item.active{background:#e8eaf6;color:#3949ab}
        .nav-icon{font-size:1.1rem}
        .main-container{flex:1;padding:24px;overflow-y:auto}
        .container{max-width:1100px;margin:0 auto}
        .welcome-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;display:flex;align-items:center;gap:20px}
        .profile-hero-avatar{width:64px;height:64px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;flex-shrink:0}
        .profile-hero-content{flex:1}
        .profile-hero-greeting{font-size:.8rem;color:#888;text-transform:uppercase;letter-spacing:.05em}
        .profile-hero-name{font-size:1.4rem;font-weight:700;color:#222;margin:4px 0}
        .profile-hero-meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:6px}
        .profile-meta-item{font-size:.8rem;color:#666;background:#f0f0f0;padding:2px 8px;border-radius:12px}
        .hidden{display:none!important}
        .user-details-card1,.user-details-card2{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px}
        .user-details-card1 h3,.user-details-card2 h3{font-size:1rem;font-weight:700;margin-bottom:12px;color:#333}
        .user-details-card1 p,.user-details-card2 p{font-size:.9rem;color:#555;margin-bottom:8px}
    </style>
</head>
<body>
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">Dashboard</span>
        </h1>
        <div class="navbar-actions">
            <div class="user-info">
                <span id="userName">User</span>
                <span class="user-badge" id="userInitials">U</span>
            </div>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon"></span>
                <span class="brand-text">Dashboard</span>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-page="dashboard" data-roles="admin,student,professor">
                    <span class="nav-icon" id="dashboardNavIcon">📊</span>
                    <span class="nav-label" id="dashboardNavLabel">Dashboard</span>
                </a>
                <a href="/notifications" class="nav-item" data-page="notifications" data-roles="admin,student,professor">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-label">Notifications</span>
                </a>
                <a href="/messages" class="nav-item" data-page="messages" data-roles="admin,student,professor">
                    <span class="nav-icon">💬</span>
                    <span class="nav-label">Messages</span>
                </a>
                <a href="/analytics" class="nav-item" data-page="analytics" data-roles="admin">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Assign Task</span>
                </a>
                <a href="/users" class="nav-item" data-page="roles" data-roles="admin">
                    <span class="nav-icon">👥</span>
                    <span class="nav-label">User Management</span>
                </a>
                <a href="/designations" class="nav-item" data-page="designations" data-roles="admin">
                    <span class="nav-icon">🏷️</span>
                    <span class="nav-label">Designations</span>
                </a>
                <a href="/departments" class="nav-item" data-page="departments" data-roles="admin">
                    <span class="nav-icon">🏛️</span>
                    <span class="nav-label">Departments</span>
                </a>
                <a href="/courses" class="nav-item" data-page="courses" data-roles="admin">
                    <span class="nav-icon">📚</span>
                    <span class="nav-label">Courses</span>
                </a>
                <a href="/subjects" class="nav-item" data-page="subjects" data-roles="admin">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-label">Subjects</span>
                </a>
                <a href="/reports" class="nav-item" data-page="reports" data-roles="admin">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label">Reports</span>
                </a>
                <a href="/profile" class="nav-item" data-page="reports" data-roles="student,professor">
                    <span class="nav-icon">👨‍💼</span>
                    <span class="nav-label">Profile</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/settings" class="nav-item settings-item" data-page="settings" data-roles="admin,student,professor">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Settings</span>
                </a>
            </div>
        </aside>

        <div class="main-container">
            <div class="container">
                <div id="errorContainer" class="error hidden"></div>

                <div class="welcome-card profile-hero-card" id="welcomeCard">
                    <div class="profile-hero-avatar" id="welcomeAvatar">U</div>
                    <div class="profile-hero-content">
                        <p class="profile-hero-greeting" id="welcomeGreeting">GOOD DAY</p>
                        <h2 class="profile-hero-name" id="welcomeTitle">Loading...</h2>
                        <div class="profile-hero-meta">
                            <span class="profile-meta-item" id="welcomeRoleMeta"></span>
                            <span class="profile-meta-item" id="welcomeUserCodeMeta">ID</span>
                            <span class="profile-meta-item" id="welcomeJoinedMeta">Joined</span>
                        </div>
                    </div>
                </div>

                     <div class="user-details-card1 hidden" id="studentDetailsCard" data-roles="student">
                    <h3>Student Details</h3>
                          <p><strong>Name:</strong> <span id="detailNameStudent">Loading...</span></p>
                          <p><strong>Course:</strong> <span id="detailCourseStudent">Loading...</span></p>
                          <p><strong>Year Level:</strong> <span id="detailYearLevelStudent">Loading...</span></p>
                          <p><strong>GWA:</strong> <span id="detailGWAStudent">Loading...</span></p>
                    
                 </div>
                
                      <div class="user-details-card2 hidden" id="professorDetailsCard" data-roles="professor">
                    <h3>Professor Details</h3>
                          <p><strong>Name:</strong> <span id="detailNameProfessor">Loading...</span></p>
                            <p><strong>Department:</strong> <span id="detailDepartmentProfessor">Loading...</span></p>
                            <p><strong>Designation:</strong> <span id="detailDesignationProfessor">Loading...</span></p>
                            
                          
                    
                 </div>
                 
            </div>
        </div>
    </div>

    <script>
        const API = {
            isAdmin: false,
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
                this.isAdmin = this.user.user_type === 'admin';
                
                this.render();
            },

            render() {
                this.renderNavbar();
                this.filterSidebarByRole();
                this.renderWelcome();
                this.renderUserDetails();
            },

            filterSidebarByRole() {
                const userRole = this.user.user_type;
                const navItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item');
                
                navItems.forEach(item => {
                    const allowedRoles = item.getAttribute('data-roles');
                    
                    if (allowedRoles) {
                        const rolesArray = allowedRoles.split(',').map(r => r.trim());
                        if (rolesArray.includes(userRole)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });
            },

            renderNavbar() {
                const navbar = document.getElementById('navbar');
                const navbarTitle = document.getElementById('navbarTitle');
                const userInitials = document.getElementById('userInitials');
                const dashboardNavIcon = document.getElementById('dashboardNavIcon');
                const dashboardNavLabel = document.getElementById('dashboardNavLabel');

                const roleTitles = {
                    admin: 'Admin Dashboard',
                    student: 'Student Portal',
                    professor: 'Professor Portal'
                };
                const navbarTitleText = document.getElementById('navbarTitleText');
                const titleTarget = navbarTitleText || navbarTitle;
                titleTarget.textContent = roleTitles[this.user.user_type] || 'Dashboard';

                if (this.isAdmin) {
                    navbar.classList.add('admin-navbar');
                    dashboardNavIcon.textContent = '📊';
                    dashboardNavLabel.textContent = 'Dashboard';
                } else {
                    dashboardNavIcon.textContent = '🏠';
                    dashboardNavLabel.textContent = 'Home';
                }

                document.getElementById('userName').textContent = this.user.name;
                
                // Calculate initials from user name (first letter of first name + first letter of last name)
                const nameParts = this.user.name.trim().split(' ');
                let initials = '';
                if (nameParts.length >= 2) {
                    initials = (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase();
                } else {
                    initials = nameParts[0].charAt(0).toUpperCase();
                }
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
            },

            renderWelcome() {
                const card = document.getElementById('welcomeCard');
                const title = document.getElementById('welcomeTitle');
                const greeting = document.getElementById('welcomeGreeting');
                const avatar = document.getElementById('welcomeAvatar');
                const roleMeta = document.getElementById('welcomeRoleMeta');
                const userCodeMeta = document.getElementById('welcomeUserCodeMeta');
                const emailMeta = document.getElementById('welcomeEmailMeta');
                const joinedMeta = document.getElementById('welcomeJoinedMeta');

                const hour = new Date().getHours();
                let greetingText = 'GOOD DAY';
                if (hour < 12) {
                    greetingText = 'GOOD MORNING';
                } else if (hour < 18) {
                    greetingText = 'GOOD AFTERNOON';
                } else {
                    greetingText = 'GOOD EVENING';
                }

                const nameParts = this.user.name.trim().split(' ');
                const initials = nameParts.length >= 2
                    ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
                    : nameParts[0].charAt(0).toUpperCase();

                greeting.textContent = greetingText;
                title.textContent = this.user.name.toUpperCase();
                if (this.user.profile_photo_url) {
                    avatar.style.backgroundImage = `url('${this.user.profile_photo_url}')`;
                    avatar.classList.add('photo');
                    avatar.textContent = '';
                } else {
                    avatar.style.backgroundImage = '';
                    avatar.classList.remove('photo');
                    avatar.textContent = initials;
                }
                roleMeta.textContent = this.user.user_type === 'admin'
                    ? 'Admin'
                    : (this.user.user_type === 'professor' ? 'Professor' : 'Student');
                userCodeMeta.textContent = this.user.user_type === 'admin'
                    ? (this.user.user_code || 'A6969')
                    : (this.user.user_code || 'No ID');
                if (emailMeta) {
                    emailMeta.textContent = this.user.email;
                }
                joinedMeta.textContent = `Joined: ${new Date(this.user.created_at).toLocaleDateString()}`;

                if (this.isAdmin) {
                    card.classList.add('admin-card');
                } else {
                    card.classList.remove('admin-card');
                }
            },

            normalizeDetailValue(value, fallback = 'Not set') {
                const normalized = String(value ?? '').trim();
                return normalized ? normalized : fallback;
            },

            renderUserDetails() {
                const studentCard = document.getElementById('studentDetailsCard');
                const professorCard = document.getElementById('professorDetailsCard');

                const role = this.user.user_type;
                const inferredProfessor = (!role || role === 'user')
                    && (this.user.designation || this.user.department);
                const inferredStudent = (!role || role === 'user')
                    && (this.user.course || this.user.year_level);

                const isProfessor = role === 'professor' || inferredProfessor;
                const isStudent = role === 'student' || (!isProfessor && inferredStudent);

                if (studentCard) {
                    studentCard.classList.toggle('hidden', !isStudent);
                }
                if (professorCard) {
                    professorCard.classList.toggle('hidden', !isProfessor);
                }

                if (isStudent) {
                    const detailName = document.getElementById('detailNameStudent');
                    const detailCourse = document.getElementById('detailCourseStudent');
                    const detailYearLevel = document.getElementById('detailYearLevelStudent');
                    const detailGWA = document.getElementById('detailGWAStudent');

                    if (detailName) {
                        detailName.textContent = this.normalizeDetailValue(this.user.name);
                    }
                    if (detailCourse) {
                        detailCourse.textContent = this.normalizeDetailValue(this.user.course);
                    }
                    if (detailYearLevel) {
                        detailYearLevel.textContent = this.normalizeDetailValue(this.user.year_level);
                    }
                    if (detailGWA) {
                        detailGWA.textContent = this.normalizeDetailValue(this.user.gwa, 'Not available');
                    }
                }

                if (isProfessor) {
                    const detailName = document.getElementById('detailNameProfessor');
                    const detailDepartment = document.getElementById('detailDepartmentProfessor');
                    const detailDesignation = document.getElementById('detailDesignationProfessor');
                    const detailGWA = document.getElementById('detailGWAProfessor');

                    if (detailName) {
                        detailName.textContent = this.normalizeDetailValue(this.user.name);
                    }
                    if (detailDepartment) {
                        detailDepartment.textContent = this.normalizeDetailValue(this.user.department);
                    }
                    if (detailDesignation) {
                        detailDesignation.textContent = this.normalizeDetailValue(this.user.designation);
                    }
                    if (detailGWA) {
                        detailGWA.textContent = this.normalizeDetailValue(this.user.gwa, 'Not available');
                    }
                }
            },

            renderAdminStats() {
                const statsSection = document.getElementById('statsSection');
                statsSection.classList.remove('hidden');

                const statsGrid = document.getElementById('statsGrid');
                const stats = [
                    { label: 'Total Users', value: '7' },
                    { label: 'Admins', value: '1' },
                    { label: 'Regular Users', value: '6' }
                ];

                statsGrid.innerHTML = stats.map(stat => `
                    <div class="stat-card admin-stat">
                        <h3>${stat.label}</h3>
                        <div class="stat-value">${stat.value}</div>
                    </div>
                `).join('');
            },

            renderActions() {
                const actionGrid = document.getElementById('actionGrid');
                if (!actionGrid) {
                    return;
                }

                const actions = this.isAdmin ? this.getAdminActions() : this.getUserActions();

                actionGrid.innerHTML = actions.map(action => `
                    <div class="action-card ${this.isAdmin ? 'admin-action' : ''}">
                        <h4>${action.title}</h4>
                        <p>${action.description}</p>
                    </div>
                `).join('');
            },

            getAdminActions() {
                return [
                    { title: 'Manage Users', description: 'Create, edit, or delete users' },
                    { title: 'System Settings', description: 'Configure system preferences' },
                    { title: 'Manage Roles', description: 'Create and assign roles' },
                    { title: 'Activity Logs', description: 'View system activity and audit logs' },
                    { title: 'Reports', description: 'Generate system reports' },
                    { title: 'Security', description: 'Manage security settings' }
                ];
            },

            getUserActions() {
                return [
                    { title: 'Edit Profile', description: 'Update your account information' },
                    { title: 'Settings', description: 'Configure your preferences' },
                    { title: 'Security', description: 'Manage your password and sessions' }
                ];
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            API.init();
            initializeSidebarNavigation();
        });

        function initializeSidebarNavigation() {
            const navItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item');
            
            navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    // Only prevent default if there's no href or it's #
                    if (!item.href || item.href.endsWith('#')) {
                        e.preventDefault();
                        
                        // Remove active class from all nav items
                        navItems.forEach(nav => nav.classList.remove('active'));
                        
                        // Add active class to clicked item
                        item.classList.add('active');
                    }
                });
            });
        }

        function logout() {
            const token = localStorage.getItem('api_token');
            
            if (!token) {
                console.warn('[logout] No token found, redirecting to login');
                localStorage.removeItem('user');
                window.location.href = '/login';
                return;
            }
            
            console.log('[logout] Sending logout request...');
            fetch('/api/auth/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            })
            .then(async response => {
                console.log('[logout] Response status:', response.status);
                let data = {};
                try { data = await response.json(); } catch (_) {}
                console.log('[logout] Response data:', data);
                localStorage.removeItem('api_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            })
            .catch(error => {
                console.error('[logout] Error:', error);
                localStorage.removeItem('api_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            });
        }
    </script>
</body>
</html>

