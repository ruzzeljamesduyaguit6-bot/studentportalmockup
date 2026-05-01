<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Role Based System</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    @vite(['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js', 'resources/js/catalog-management-loader.js'])
</head>
<body data-catalog-type="courses" data-catalog-label="Course">
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">Courses</span>
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
                <a href="/courses" class="nav-item active" data-page="courses" data-roles="admin">
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
                    <h2>Courses</h2>
                    <div class="users-header-actions">
                        <button class="add-user-btn" onclick="openAddCatalogModal()">Add Course</button>
                    </div>
                </div>

                <div class="search-bar">
                    <input type="text" id="catalogSearchInput" placeholder="Search courses by name or code..." onkeyup="filterCatalogItems()">
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
                            <th>Degree</th>
                            <th>Department</th>
                            <th>Years</th>
                            <th>Assigned Subjects</th>
                            <th>Total Units</th>
                            <th class="table-action-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="catalogTableBody">
                        <tr>
                            <td colspan="9" class="loading">Loading courses...</td>
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
                <h3 id="addCatalogModalTitle">Add Course</h3>
                <button type="button" class="modal-close" onclick="closeAddCatalogModal()" aria-label="Close">&times;</button>
            </div>
            <form id="addCatalogForm" class="modal-form">
                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="courseDegreeLevelInput">Degree Level</label>
                        <select id="courseDegreeLevelInput" required></select>
                    </div>
                    <div class="form-field">
                        <label for="courseDepartmentInput">Department</label>
                        <select id="courseDepartmentInput" required></select>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="courseProgramInput">Program Title</label>
                        <input id="courseProgramInput" type="text" placeholder="Information Systems" required>
                    </div>
                    <div class="form-field">
                        <label for="courseYearsInput">Total Years to Finish</label>
                        <input id="courseYearsInput" type="number" min="1" max="10" step="1" placeholder="4" required>
                    </div>
                </div>

                <div class="modal-grid two-cols">
                    <div class="form-field">
                        <label for="catalogNameInput">Course Name</label>
                        <input id="catalogNameInput" type="text" placeholder="BS Information Systems" required>
                    </div>
                    <div class="form-field">
                        <label for="courseCodeInput">Course Code</label>
                        <input id="courseCodeInput" type="text" placeholder="BS-IS" required>
                    </div>
                </div>

                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label>Assign Subjects</label>
                        <div id="courseAddSubjectsChecklist" class="catalog-checkbox-list">
                            <p class="catalog-checkbox-empty">Select a department to load subjects.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeAddCatalogModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editCatalogModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="editCatalogModalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="editCatalogModalTitle">Edit Course Subjects</h3>
                <button type="button" class="modal-close" onclick="closeEditCatalogModal()" aria-label="Close">&times;</button>
            </div>
            <form id="editCatalogForm" class="modal-form">
                <input type="hidden" id="editCatalogId">
                <div class="modal-grid one-col">
                    <div class="form-field">
                        <label>Assigned Subjects</label>
                        <div id="courseSubjectsChecklist" class="catalog-checkbox-list">
                            <p class="catalog-checkbox-empty">Loading subjects...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn" onclick="closeEditCatalogModal()">Cancel</button>
                    <button type="submit" class="add-user-btn">Update Assignments</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
