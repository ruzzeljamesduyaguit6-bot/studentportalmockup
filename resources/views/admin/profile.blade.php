<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Profile - Student Portal</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js', 'resources/js/profile-loader.js']" />
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
        .hidden{display:none!important}
        .profile-page-shell{display:flex;flex-direction:column;gap:20px}
        .profile-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
        .profile-card h3{font-size:1rem;font-weight:700;margin-bottom:14px;color:#333}
        .profile-summary-card{display:flex;align-items:center;gap:20px}
        .profile-avatar-wrap{display:flex;flex-direction:column;align-items:center;gap:8px}
        .profile-avatar{width:80px;height:80px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700}
        .profile-photo-upload{font-size:.8rem;color:#667eea;cursor:pointer;text-decoration:underline}
        .profile-summary-content{flex:1}
        .profile-summary-content h2{font-size:1.3rem;font-weight:700;color:#222;margin-bottom:4px}
        .profile-role-line,.profile-code-line{font-size:.9rem;color:#666;margin-bottom:4px}
        .profile-grid-two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .profile-stack{display:flex;flex-direction:column;gap:20px}
        .profile-form .form-field{margin-bottom:14px}
        .profile-form label{display:block;font-size:.85rem;font-weight:600;color:#444;margin-bottom:4px}
        .profile-form input{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem}
        .add-user-btn{padding:8px 16px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.9rem;font-weight:600}
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000}
        .modal-card{background:#fff;border-radius:12px;padding:24px;width:100%;max-width:420px}
        .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
        .modal-header h3{font-size:1.1rem;font-weight:700}
        .modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888}
        .modal-form .form-field{margin-bottom:14px}
        .modal-form label{display:block;font-size:.85rem;font-weight:600;color:#444;margin-bottom:4px}
        .modal-form input{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem}
        .modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
        .action-btn{padding:6px 12px;background:#f0f0f0;color:#333;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:.85rem}
        .profile-note{font-size:.85rem;color:#888;margin-bottom:10px}
        .verification-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.8rem;font-weight:600}
        .verification-badge.pending{background:#fff3cd;color:#856404}
        .verified-status-banner{background:#d4edda;color:#155724;padding:8px 12px;border-radius:6px;font-size:.9rem;margin-bottom:10px}
        .modal-grid.two-cols{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    </style>
</head>
<body>
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">Profile</span>
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
                <a href="/dashboard" class="nav-item" data-page="dashboard" data-roles="admin,student,professor">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
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
                <a href="/profile" class="nav-item active" data-page="profile" data-roles="student,professor">
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
            <div class="container profile-page-shell">
                <div id="profileMessage" class="hidden"></div>

                <div class="profile-card profile-summary-card">
                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar" id="profileAvatar">U</div>
                        <label class="profile-photo-upload" for="profilePhotoInput">Upload Photo</label>
                        <input id="profilePhotoInput" type="file" accept="image/*" class="hidden">
                    </div>
                    <div class="profile-summary-content">
                        <h2 id="profileDisplayName">Loading...</h2>
                        <p class="profile-role-line" id="profileRoleLine">Role</p>
                        <p class="profile-code-line" id="profileCodeLine">ID</p>
                        <div class="profile-verification-row">
                            <span id="verificationBadge" class="verification-badge pending">Not Verified</span>
                        </div>
                    </div>
                </div>

                <div class="profile-card profile-progress-card">
                    <div class="profile-progress-head">
                        <h3>Profile Completion</h3>
                        <span id="profileProgressLabel">0%</span>
                    </div>
                    <div class="profile-progress-bar-wrap">
                        <div id="profileProgressBar" class="profile-progress-bar"></div>
                    </div>
                    <p class="profile-progress-meta" id="profileProgressMeta">0 / 0 completed</p>
                </div>

                <div class="profile-grid-two">
                    <div class="profile-card">
                        <h3>Edit Profile Information</h3>
                        <form id="profileForm" class="profile-form">
                            <div class="modal-grid two-cols">
                                <div class="form-field">
                                    <label for="profileName">Full Name</label>
                                    <input id="profileName" type="text" required>
                                </div>
                                <div class="form-field">
                                    <label for="profileEmail">Email</label>
                                    <input id="profileEmail" type="email" required>
                                </div>
                            </div>

                            <div class="modal-grid two-cols">
                                <div class="form-field">
                                    <label for="profileBirthday">Birthday</label>
                                    <input id="profileBirthday" type="date">
                                </div>
                                <div class="form-field">
                                    <label for="profileContact">Contact</label>
                                    <input id="profileContact" type="text" placeholder="09XXXXXXXXX">
                                </div>
                            </div>
                            <!-- hidden for students -->
                            <div id="professorReadonlyFields" class="modal-grid two-cols hidden" data-roles="professor">
                                <div class="form-field" data-roles="professor">
                                    <label for="profileDesignation">Designation</label>
                                    <input id="profileDesignation" type="text" data-roles="professor" readonly>
                                </div>
                                <div class="form-field" data-roles="professor">
                                    <label for="profileDepartment">Department</label>
                                    <input id="profileDepartment" type="text" data-roles="professor" readonly>
                                </div>
                            </div>
                            
                            <div id="studentReadonlyFields" class="modal-grid two-cols hidden" data-roles="student">
                                <div class="form-field" data-roles="student">
                                    <label for="profileCourse">Course</label>
                                    <input id="profileCourse" type="text" readonly>
                                </div>
                                <div class="form-field" data-roles="student">
                                    <label for="profileYearLevel">Year Level</label>
                                    <input id="profileYearLevel" type="text" readonly>
                                </div>
                            </div>

                            <button type="submit" class="add-user-btn">Save Profile</button>
                        </form>
                    </div>

                    <div class="profile-stack">
                        <div class="profile-card">
                            <h3>Email Verification</h3>
                            <p class="profile-note">Receive a 6-digit code in your email, then enter it below.</p>
                            <div id="verifiedStatus" class="verified-status-banner hidden">Email verified successfully ✓</div>
                            <div id="verificationActions">
                                <button id="openVerifyEmailModalBtn" type="button" class="add-user-btn">Verify Email</button>
                            </div>
                        </div>

                        <div class="profile-card">
                            <h3>Change Password</h3>
                            <form id="passwordForm" class="profile-form">
                                <div class="form-field">
                                    <label for="currentPassword">Current Password</label>
                                    <input id="currentPassword" type="password" required>
                                </div>
                                <div class="form-field">
                                    <label for="newPassword">New Password</label>
                                    <input id="newPassword" type="password" required>
                                </div>
                                <div class="form-field">
                                    <label for="newPasswordConfirmation">Confirm New Password</label>
                                    <input id="newPasswordConfirmation" type="password" required>
                                </div>
                                <button type="submit" class="add-user-btn">Update Password</button>
                            </form>
                        </div>

                        <div class="profile-card" id="pendingRequestCard">
                            <h3>Pending Approval</h3>
                            <p class="profile-note" id="pendingRequestText">No pending dropdown approval request.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="verifyEmailModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="verifyEmailModalTitle">
        <div class="modal-card verify-email-modal-card">
            <div class="modal-header">
                <h3 id="verifyEmailModalTitle">Verify Email</h3>
                <button id="closeVerifyEmailModalX" type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-form">
                <p class="profile-note">A 6-digit verification code was sent to your email.</p>
                <p id="verifyAttemptInfo" class="verify-attempt-info">You have 3 attempts.</p>

                <div class="form-field">
                    <label for="verifyEmailModalCode">Verification Code</label>
                    <input id="verifyEmailModalCode" type="text" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter 6-digit code">
                </div>

                <div class="modal-actions">
                    <button id="closeVerifyEmailModalBtn" type="button" class="action-btn">Cancel</button>
                    <button id="submitVerifyEmailModalBtn" type="button" class="add-user-btn">Verify Code</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
