<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - Role Based System</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js', 'resources/js/catalog-management-loader.js']" />
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
        .add-user-btn{padding:8px 16px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.9rem;font-weight:600}
        .action-btn{padding:6px 12px;background:#f0f0f0;color:#333;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:.85rem}
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
        .role-filter-bar{display:flex;gap:8px;margin-bottom:12px;align-items:center}
        .pagination-wrap{display:flex;align-items:center;gap:12px;margin-top:16px;justify-content:center}
        .pagination-info{font-size:.85rem;color:#666}
        .catalog-checkbox-list{border:1px solid #ddd;border-radius:6px;padding:10px;max-height:180px;overflow-y:auto}
        .catalog-checkbox-item{display:flex;align-items:center;gap:8px;padding:4px 0;font-size:.9rem;cursor:pointer}
    </style>
</head>
<body data-catalog-type="subjects" data-catalog-label="Subject">
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">Subjects</span>
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
                <a href="/subjects" class="nav-item active" data-page="subjects" data-roles="admin">
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
                    <h2>Subjects</h2>
                    <div class="users-header-actions">
                        <button class="add-user-btn" onclick="openAddCatalogModal()">Add Subject</button>
                    </div>
                </div>

                <div class="search-bar">
                    <input type="text" id="catalogSearchInput" placeholder="Search subjects by name or code..." onkeyup="filterCatalogItems()">
                </div>

                <div class="role-filter-bar">
                    <select id="catalogDepartmentFilter" class="action-btn role-filter"></select>
                    <button class="action-btn role-filter" onclick="applyDepartmentFilter()">Apply Department Filter</button>
                    <button class="action-btn role-filter" onclick="clearDepartmentFilter()">Clear Filter</button>
                </div>

                <div class="users-count compact-indicator" id="catalogStats"></div>

                <table class="users-table" id="catalogTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Units</th>
                            <th>Free for All</th>
                            <th class="table-action-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="catalogTableBody">
                        <tr>
                            <td colspan="7" class="loading">Loading subjects...</td>
                        </tr>
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    <button class="action-btn" id="catalogPrevPageBtn" onclick="changeCatalogPage(-1)">Previous</button>
                    <span class="pagination-info" id="catalogPaginationInfo">Page 1 of 1</span>
                    <button class="action-btn" id="catalogNextPageBtn" onclick="changeCatalogPage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>

    <div id="addCatalogModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="addCatalogModalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="addCatalogModalTitle">Add Subject</h3>
                <button type="button" class="modal-close" onclick="closeAddCatalogModal()" aria-label="Close">&times;</button>
            </div>
            <form id="addCatalogForm" class="modal-form">
                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="catalogNameInput">Subject Name</label>
                        <input id="catalogNameInput" type="text" placeholder="Information System 1" required>
                    </div>
                    <div class="form-field">
                        <label for="subjectCodeInput">Subject Code</label>
                        <input id="subjectCodeInput" type="text" placeholder="IS1" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="subjectUnitsInput">Units</label>
                        <input id="subjectUnitsInput" type="number" min="1" max="12" step="1" placeholder="3" required>
                    </div>
                    <div class="form-field">
                        <label for="subjectDepartmentInput">Department</label>
                        <select id="subjectDepartmentInput"></select>
                    </div>
                </div>

                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label class="catalog-checkbox-item" for="subjectFreeForAllInput">
                            <input id="subjectFreeForAllInput" type="checkbox">
                            <span>Free for all departments (ex: PE)</span>
                        </label>
                    </div>
                </div>

                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label>Assign to Courses</label>
                        <div id="subjectCoursesChecklist" class="catalog-checkbox-list">
                            <p class="catalog-checkbox-empty">Select a department or mark Free for all to load courses.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeAddCatalogModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Add Subject</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editCatalogModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="editCatalogModalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="editCatalogModalTitle">Edit Subject</h3>
                <button type="button" class="modal-close" onclick="closeEditCatalogModal()" aria-label="Close">&times;</button>
            </div>
            <form id="editCatalogForm" class="modal-form">
                <input type="hidden" id="editCatalogId">

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="editSubjectNameInput">Subject Name</label>
                        <input id="editSubjectNameInput" type="text" required>
                    </div>
                    <div class="form-field">
                        <label for="editSubjectCodeInput">Subject Code</label>
                        <input id="editSubjectCodeInput" type="text" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="editSubjectUnitsInput">Units</label>
                        <input id="editSubjectUnitsInput" type="number" min="1" max="12" step="1" required>
                    </div>
                    <div class="form-field">
                        <label for="editSubjectDepartmentInput">Department</label>
                        <select id="editSubjectDepartmentInput"></select>
                    </div>
                </div>

                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label class="catalog-checkbox-item" for="editSubjectFreeForAllInput">
                            <input id="editSubjectFreeForAllInput" type="checkbox">
                            <span>Free for all departments (ex: PE)</span>
                        </label>
                    </div>
                </div>

                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label>Assigned Courses</label>
                        <div id="editSubjectCoursesChecklist" class="catalog-checkbox-list">
                            <p class="catalog-checkbox-empty">Loading courses...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeEditCatalogModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
