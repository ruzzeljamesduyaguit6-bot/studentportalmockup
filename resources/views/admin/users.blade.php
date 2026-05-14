<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">User Management - Role Based System</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js', 'resources/js/user-management-loader.js']" />
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
        .users-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
        .users-header h2{font-size:1.3rem;font-weight:700;color:#222}
        .users-header-actions{display:flex;gap:8px}
        .add-user-btn{padding:8px 16px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.9rem;font-weight:600}
        .action-btn{padding:6px 12px;background:#f0f0f0;color:#333;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:.85rem}
        .action-btn:disabled{opacity:.5;cursor:not-allowed}
        .search-bar{margin-bottom:12px}
        .search-bar input{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem}
        .users-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
        .users-table th{background:#f8f9fa;padding:12px 14px;text-align:left;font-size:.85rem;font-weight:600;color:#555;border-bottom:1px solid #e0e0e0}
        .users-table td{padding:12px 14px;font-size:.9rem;color:#333;border-bottom:1px solid #f0f0f0}
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000}
        .modal-card{background:#fff;border-radius:12px;padding:24px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto}
        .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
        .modal-header h3{font-size:1.1rem;font-weight:700}
        .modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888}
        .modal-form .form-field{margin-bottom:14px}
        .modal-form label{display:block;font-size:.85rem;font-weight:600;color:#444;margin-bottom:4px}
        .modal-form input,.modal-form select{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem}
        .modal-grid.two-cols{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .modal-grid.one-col{display:grid;grid-template-columns:1fr;gap:12px}
        .modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
        .conditional-fields.hidden{display:none}
        .role-filter-bar{display:flex;gap:8px;margin-bottom:12px}
        .pagination-wrap{display:flex;align-items:center;gap:12px;margin-top:16px;justify-content:center}
        .pagination-info{font-size:.85rem;color:#666}
    </style>
</head>
<body>
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">User Management</span>
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
                <a href="/users" class="nav-item active" data-page="roles" data-roles="admin">
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
                <div id="errorContainer" class="hidden"></div>

                <div class="users-header">
                    <h2>User Management</h2>
                    <div class="users-header-actions">
                        <button class="action-btn delete" id="deleteSelectedBtn" onclick="deleteSelectedUsers()" disabled>Delete Selected</button>
                        <button class="add-user-btn" onclick="openAddUserModal()">+ Add User</button>
                    </div>
                </div>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search users by name or user ID..." onkeyup="filterUsers()">
                </div>

                <div class="role-filter-bar">
                    <button class="action-btn role-filter active" id="filterAllBtn" onclick="setUserRoleFilter('all')">All</button>
                    <button class="action-btn role-filter" id="filterStudentBtn" onclick="setUserRoleFilter('student')">Students</button>
                    <button class="action-btn role-filter" id="filterProfessorBtn" onclick="setUserRoleFilter('professor')">Professors</button>
                </div>

                <div class="users-count" id="userStats">
                    <!-- Stats will be populated here -->
                </div>

                <div id="loadingContainer" class="loading hidden">
                    <div class="spinner"></div>
                    <p>Loading users...</p>
                </div>

                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAllUsers" onchange="toggleSelectAll(this.checked)">
                            </th>
                            <th>Name</th>
                            <th>User ID</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="6" class="loading">
                                <div class="spinner"></div>
                                <p>Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="pagination-wrap" id="paginationWrap">
                    <button class="action-btn" id="prevPageBtn" onclick="changePage(-1)">Previous</button>
                    <span class="pagination-info" id="paginationInfo">Page 1 of 1</span>
                    <button class="action-btn" id="nextPageBtn" onclick="changePage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>

    <div id="addUserModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="addUserModalTitle">Add User</h3>
                <button type="button" class="modal-close" onclick="closeAddUserModal()" aria-label="Close">&times;</button>
            </div>
            <form id="addUserForm" class="modal-form">
                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="newUserRole">Role</label>
                        <select id="newUserRole" required>
                            <option value="student" selected>Student</option>
                            <option value="professor">Professor</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="newUserCode">ID</label>
                        <input id="newUserCode" type="text" readonly>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="newUserName">Name</label>
                        <input id="newUserName" type="text" placeholder="Juan Dela Cruz" required>
                    </div>
                    <div class="form-field">
                        <label for="newUserBirthday">Birthday</label>
                        <input id="newUserBirthday" type="date" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="newUserEmail">Email</label>
                        <input id="newUserEmail" type="email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-field">
                        <label for="newUserContact">Contact</label>
                        <input id="newUserContact" type="text" placeholder="09XXXXXXXXX" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="newUserPassword">Password</label>
                        <input id="newUserPassword" type="password" required>
                    </div>
                    <div class="form-field">
                        <label for="newUserConfirmPassword">Confirm Password</label>
                        <input id="newUserConfirmPassword" type="password" required>
                    </div>
                </div>

                <div id="professorFields" class="conditional-fields hidden">
                    <div class="modal-grid two-cols">
                        <div class="form-field">
                            <label for="newProfessorDesignation">Professor Designation</label>
                            <select id="newProfessorDesignation">
                                <option value="">Select designation</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="newProfessorDepartment">Professor Department</label>
                            <select id="newProfessorDepartment">
                                <option value="">Select department</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="studentFields" class="conditional-fields">
                    <div class="modal-grid two-cols">
                        <div class="form-field">
                            <label for="newStudentDepartment">Student Department</label>
                            <select id="newStudentDepartment">
                                <option value="">Select department</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="newStudentCourse">Student Course</label>
                            <select id="newStudentCourse">
                                <option value="">Select course</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-grid one-col">
                        <div class="form-field">
                            <label for="newStudentYearLevel">Year Level</label>
                            <select id="newStudentYearLevel">
                                <option value="">Select year level</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="editUserModalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="editUserModalTitle">Edit User</h3>
                <button type="button" class="modal-close" onclick="closeEditUserModal()" aria-label="Close">&times;</button>
            </div>
            <form id="editUserForm" class="modal-form">
                <input id="editUserId" type="hidden">

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="editUserRole">Role</label>
                        <select id="editUserRole" required>
                            <option value="student">Student</option>
                            <option value="professor">Professor</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="editUserCode">ID</label>
                        <input id="editUserCode" type="text" readonly>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="editUserName">Name</label>
                        <input id="editUserName" type="text" required>
                    </div>
                    <div class="form-field">
                        <label for="editUserBirthday">Birthday</label>
                        <input id="editUserBirthday" type="date" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="editUserEmail">Email</label>
                        <input id="editUserEmail" type="email" required>
                    </div>
                    <div class="form-field">
                        <label for="editUserContact">Contact</label>
                        <input id="editUserContact" type="text" required>
                    </div>
                </div>

                <div id="editProfessorFields" class="conditional-fields hidden">
                    <div class="modal-grid two-cols">
                        <div class="form-field">
                            <label for="editProfessorDesignation">Professor Designation</label>
                            <select id="editProfessorDesignation">
                                <option value="">Select designation</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="editProfessorDepartment">Professor Department</label>
                            <select id="editProfessorDepartment">
                                <option value="">Select department</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-grid one-col">
                        <div class="form-field">
                            <label for="editProfessorSubject">Subject</label>
                            <select id="editProfessorSubject">
                                <option value="">Select subject</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="editStudentFields" class="conditional-fields">
                    <div class="modal-grid two-cols">
                        <div class="form-field">
                            <label for="editStudentDepartment">Student Department</label>
                            <select id="editStudentDepartment">
                                <option value="">Select department</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="editStudentCourse">Student Course</label>
                            <select id="editStudentCourse">
                                <option value="">Select course</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-grid one-col">
                        <div class="form-field">
                            <label for="editStudentSubject">Subject</label>
                            <select id="editStudentSubject">
                                <option value="">Select subject</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-grid one-col">
                        <div class="form-field">
                            <label for="editStudentYearLevel">Year Level</label>
                            <select id="editStudentYearLevel">
                                <option value="">Select year level</option>
                                <option>1st Year</option>
                                <option>2nd Year</option>
                                <option>3rd Year</option>
                                <option>4th Year</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Update User</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
