const API = {
    user: null,
    users: [],
    filteredUsers: [],
    catalogOptions: {
        designations: [],
        departments: [],
        courses: [],
        subjects: [],
    },
    selectedUsers: new Set(),
    currentPage: 1,
    pageSize: 15,
    searchTerm: '',
    roleFilter: 'all',

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

        if (this.user.user_type !== 'admin') {
            window.location.href = '/dashboard';
            return;
        }

        this.render();
        this.loadPageData();
        this.loadCatalogOptions();
        this.loadUsers();
    },

    render() {
        this.renderNavbar();
        this.filterSidebarByRole();
    },

    renderNavbar() {
        const userName = document.getElementById('userName');
        const userInitials = document.getElementById('userInitials');
        const navbar = document.getElementById('navbar');

        if (userName) {
            userName.textContent = this.user.name;
        }

        const nameParts = this.user.name.trim().split(' ');
        const initials = nameParts.length >= 2
            ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
            : nameParts[0].charAt(0).toUpperCase();

        if (userInitials) {
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
        }

        if (navbar) {
            navbar.classList.add('admin-navbar');
        }
    },

    filterSidebarByRole() {
        const userRole = this.user.user_type;
        const navItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item');

        navItems.forEach(item => {
            const allowedRoles = item.getAttribute('data-roles');
            if (!allowedRoles) {
                return;
            }
            const rolesArray = allowedRoles.split(',').map(r => r.trim());
            item.style.display = rolesArray.includes(userRole) ? 'flex' : 'none';
        });
    },

    async loadPageData() {
        try {
            const token = localStorage.getItem('api_token');
            const response = await fetch('/api/user-management/data', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load page data');
            }
        } catch (error) {
            console.error('Error loading page data:', error);
        }
    },

    async loadCatalogOptions() {
        try {
            const token = localStorage.getItem('api_token');
            const response = await fetch('/api/catalog/options', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load catalog options');
            }

            const data = await response.json();
            this.catalogOptions = {
                designations: data.designations || [],
                departments: data.departments || [],
                courses: data.courses || [],
                subjects: data.subjects || [],
                departmentOptions: data.department_options || [],
                courseOptions: data.course_options || [],
                subjectOptions: data.subject_options || [],
                degreeLevels: data.degree_levels || [],
            };

            populateCatalogSelects(this.catalogOptions);
        } catch (error) {
            console.error('Error loading catalog options:', error);
        }
    },

    async loadUsers() {
        const loadingContainer = document.getElementById('loadingContainer');
        loadingContainer.classList.remove('hidden');

        try {
            const token = localStorage.getItem('api_token');
            const response = await fetch('/api/user-management/users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load users');
            }

            const data = await response.json();
            this.users = data.users || [];
            this.applyFilters();
            this.selectedUsers.clear();

            loadingContainer.classList.add('hidden');
            this.renderUsers();
            this.renderStats(data.stats);
        } catch (error) {
            console.error('Error loading users:', error);
            loadingContainer.classList.add('hidden');
            document.getElementById('errorContainer').innerHTML = '<div class="error">Failed to load users. Please try again.</div>';
            document.getElementById('errorContainer').classList.remove('hidden');
        }
    },

    searchUsers(query) {
        this.searchTerm = query.toLowerCase();
        this.currentPage = 1;
        this.applyFilters();
        this.renderUsers();
    },

    setRoleFilter(role) {
        this.roleFilter = role;
        this.currentPage = 1;
        this.applyFilters();
        this.renderUsers();
    },

    applyFilters() {
        const searchTerm = (this.searchTerm || '').trim();

        this.filteredUsers = this.users.filter(user => {
            const rolePass = this.roleFilter === 'all' || user.user_type === this.roleFilter;
            if (!rolePass) {
                return false;
            }

            if (!searchTerm) {
                return true;
            }

            const nameMatch = user.name.toLowerCase().includes(searchTerm);
            const userIdMatch = (user.user_code || '').toLowerCase().includes(searchTerm);
            return nameMatch || userIdMatch;
        });

        updateRoleFilterButtons(this.roleFilter);
    },

    getPagedUsers() {
        const totalPages = Math.max(1, Math.ceil(this.filteredUsers.length / this.pageSize));
        if (this.currentPage > totalPages) {
            this.currentPage = totalPages;
        }
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;

        return {
            users: this.filteredUsers.slice(start, end),
            totalPages,
        };
    },

    renderUsers() {
        const tbody = document.getElementById('usersTableBody');
        const { users, totalPages } = this.getPagedUsers();

        if (this.filteredUsers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">No users found</td></tr>';
            this.renderPagination(totalPages);
            this.updateSelectionControls();
            return;
        }

        tbody.innerHTML = users.map(user => {
            const joinDate = new Date(user.created_at);
            const initials = this.getInitials(user.name);
            const avatarColor = user.user_type === 'admin' ? '#667eea' : '#17a2b8';
            const avatarStyle = user.profile_photo_url
                ? `background-image:url('${user.profile_photo_url}');background-size:cover;background-position:center;`
                : `background:${avatarColor};`;
            const avatarText = user.profile_photo_url ? '' : initials;
            const userCode = user.user_code || 'No ID';
            const isAdmin = user.user_type === 'admin';
            if (isAdmin) {
                this.selectedUsers.delete(user.id);
            }
            const checked = this.selectedUsers.has(user.id) ? 'checked' : '';
            const editable = user.user_type === 'student' || user.user_type === 'professor';
            const editDisabled = editable ? '' : 'disabled';
            const deleteDisabled = isAdmin ? 'disabled title="Admin accounts cannot be deleted"' : '';
            const selectDisabled = isAdmin ? 'disabled title="Admin accounts cannot be selected"' : '';
            const verifiedBadge = user.email_verified_at
                ? '<span class="verified-mini-badge" title="Email verified">✓</span>'
                : '';

            return `
                <tr>
                    <td>
                        <input type="checkbox" ${checked} ${selectDisabled} onchange="toggleUserSelection(${user.id}, this.checked)">
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="${avatarStyle}">${avatarText}</div>
                            <div class="user-info">
                                <div class="user-name">${user.name} ${verifiedBadge}</div>
                            </div>
                        </div>
                    </td>
                    <td>${userCode}</td>
                    <td>
                        <span class="role-badge ${user.user_type}">${user.user_type}</span>
                    </td>
                    <td>${joinDate.toLocaleDateString()}</td>
                    <td>
                        <div class="user-actions">
                            <button class="action-btn" ${editDisabled} onclick="editUser(${user.id})">Edit</button>
                            <button class="action-btn delete" ${deleteDisabled} onclick="deleteUser(${user.id})">Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        this.renderPagination(totalPages);
        this.updateSelectionControls();
    },

    renderPagination(totalPages) {
        const paginationInfo = document.getElementById('paginationInfo');
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');

        if (paginationInfo) {
            paginationInfo.textContent = `Page ${this.currentPage} of ${totalPages}`;
        }
        if (prevBtn) {
            prevBtn.disabled = this.currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = this.currentPage >= totalPages;
        }
    },

    updateSelectionControls() {
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const selectAll = document.getElementById('selectAllUsers');
        const { users } = this.getPagedUsers();
        const selectablePageIds = users
            .filter(user => user.user_type !== 'admin')
            .map(user => user.id);
        const allCheckedOnPage = selectablePageIds.length > 0
            && selectablePageIds.every(id => this.selectedUsers.has(id));

        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = this.selectedUsers.size === 0;
            deleteSelectedBtn.textContent = this.selectedUsers.size > 0
                ? `Delete Selected (${this.selectedUsers.size})`
                : 'Delete Selected';
        }

        if (selectAll) {
            selectAll.checked = allCheckedOnPage;
        }
    },

    renderStats(stats) {
        const statsContainer = document.getElementById('userStats');
        statsContainer.innerHTML = `
            <div class="count-card">
                <div class="count-label">Total Users</div>
                <div class="count-value">${stats.totalUsers}</div>
            </div>
            <div class="count-card">
                <div class="count-label">Students</div>
                <div class="count-value">${stats.students}</div>
            </div>
            <div class="count-card">
                <div class="count-label">Professors</div>
                <div class="count-value">${stats.professors}</div>
            </div>
            <div class="count-card">
                <div class="count-label">Admins</div>
                <div class="count-value">${stats.admins}</div>
            </div>
        `;
    },

    getInitials(name) {
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return parts[0].charAt(0).toUpperCase();
    }
};

function initializeSidebarNavigation() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-footer .nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            if (!item.href || item.href.endsWith('#')) {
                e.preventDefault();
                
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            }
        });
    });
}

function filterUsers() {
    const searchInput = document.getElementById('searchInput').value;
    API.searchUsers(searchInput);
}

function setUserRoleFilter(role) {
    API.setRoleFilter(role);
}

function updateRoleFilterButtons(activeRole) {
    const filters = ['all', 'student', 'professor'];
    filters.forEach(role => {
        const suffix = role === 'all'
            ? 'All'
            : (role === 'student' ? 'Student' : 'Professor');
        const button = document.getElementById(`filter${suffix}Btn`);
        if (!button) {
            return;
        }

        if (role === activeRole) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

function changePage(delta) {
    const totalPages = Math.max(1, Math.ceil(API.filteredUsers.length / API.pageSize));
    const nextPage = API.currentPage + delta;

    if (nextPage < 1 || nextPage > totalPages) {
        return;
    }

    API.currentPage = nextPage;
    API.renderUsers();
}

function toggleUserSelection(userId, isChecked) {
    const user = API.users.find(item => Number(item.id) === Number(userId));
    if (user && user.user_type === 'admin') {
        API.selectedUsers.delete(userId);
        API.updateSelectionControls();
        return;
    }

    if (isChecked) {
        API.selectedUsers.add(userId);
    } else {
        API.selectedUsers.delete(userId);
    }

    API.updateSelectionControls();
}

function toggleSelectAll(isChecked) {
    const { users } = API.getPagedUsers();
    users.forEach(user => {
        if (user.user_type === 'admin') {
            API.selectedUsers.delete(user.id);
            return;
        }

        if (isChecked) {
            API.selectedUsers.add(user.id);
        } else {
            API.selectedUsers.delete(user.id);
        }
    });
    API.renderUsers();
}

async function deleteSelectedUsers() {
    if (API.selectedUsers.size === 0) {
        return;
    }

    const nonAdminIds = Array.from(API.selectedUsers).filter((id) => {
        const user = API.users.find((item) => Number(item.id) === Number(id));
        return user && user.user_type !== 'admin';
    });

    if (nonAdminIds.length === 0) {
        alert('Admin accounts cannot be deleted.');
        API.selectedUsers.clear();
        API.renderUsers();
        return;
    }

    if (!confirm(`Are you sure you want to delete ${nonAdminIds.length} selected users?`)) {
        return;
    }

    try {
        const token = localStorage.getItem('api_token');
        const response = await fetch('/api/user-management/users/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ ids: nonAdminIds })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to delete selected users');
        }

        const deletedCurrentUser = API.user && API.selectedUsers.has(API.user.id);

        alert(data.message || 'Selected users deleted successfully');
        if (deletedCurrentUser) {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
            return;
        }

        API.loadUsers();
    } catch (error) {
        alert(error.message || 'Error deleting selected users');
    }
}

async function editUser(userId) {
    const user = API.users.find(u => u.id === userId);
    if (!user) {
        alert('User not found');
        return;
    }

    if (user.user_type === 'admin') {
        alert('Admin accounts are not editable in this form.');
        return;
    }

    await API.loadCatalogOptions();

    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserName').value = user.name || '';
    document.getElementById('editUserBirthday').value = user.birthday || '';
    document.getElementById('editUserEmail').value = user.email || '';
    document.getElementById('editUserContact').value = user.contact || '';
    document.getElementById('editUserRole').value = ['student', 'professor'].includes(user.user_type) ? user.user_type : 'student';
    document.getElementById('editUserCode').value = user.user_code || '';

    setSelectValueWithFallback('editProfessorDesignation', user.designation || '');
    setSelectValueWithFallback('editProfessorDepartment', user.department || '');
    setSelectValueWithFallback('editStudentDepartment', user.department || '');
    refreshStudentCourseOptions('editStudentDepartment', 'editStudentCourse', user.course || '');
    setSelectValueWithFallback('editProfessorSubject', user.subject || '');
    setSelectValueWithFallback('editStudentSubject', user.subject || '');
    refreshYearLevelOptionsFromCourse('editStudentCourse', 'editStudentYearLevel', user.year_level || '');

    updateEditRoleSpecificFields();
    openEditUserModal();
}

function deleteUser(userId) {
    const user = API.users.find(u => u.id === userId);
    if (!user) {
        alert('User not found');
        return;
    }

    if (user.user_type === 'admin') {
        alert('Admin accounts cannot be deleted.');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${user.name}?`)) {
        return;
    }

    // Call delete API
    const token = localStorage.getItem('api_token');
    fetch(`/api/user-management/users/${userId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const deletedCurrentUser = API.user && Number(API.user.id) === Number(userId);
            alert('User deleted successfully');
            if (deletedCurrentUser) {
                localStorage.removeItem('api_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
                return;
            }

            API.loadUsers();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete user'));
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        alert('Error deleting user');
    });
}

async function openAddUserModal() {
    const modal = document.getElementById('addUserModal');
    const form = document.getElementById('addUserForm');
    if (!modal || !form) return;

    await API.loadCatalogOptions();

    form.reset();
    document.getElementById('newUserRole').value = 'student';
    updateRoleSpecificFields();
    updateGeneratedUserCode();
    refreshStudentCourseOptions('newStudentDepartment', 'newStudentCourse');
    refreshYearLevelOptionsFromCourse('newStudentCourse', 'newStudentYearLevel');

    modal.classList.remove('hidden');
}

function closeAddUserModal() {
    const modal = document.getElementById('addUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
}

function openEditUserModal() {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    modal.classList.remove('hidden');
}

function closeEditUserModal() {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
}

function getCatalogCourseOptions() {
    if (!API.catalogOptions || !Array.isArray(API.catalogOptions.courseOptions)) {
        return [];
    }

    return API.catalogOptions.courseOptions;
}

function getCourseByName(name) {
    const normalizedName = String(name || '').trim();
    if (!normalizedName) {
        return null;
    }

    return getCatalogCourseOptions().find((course) => String(course.name || '').trim() === normalizedName) || null;
}

function getCoursesForDepartmentName(departmentName) {
    const normalizedDepartment = String(departmentName || '').trim();
    const courses = getCatalogCourseOptions();

    if (!normalizedDepartment) {
        return courses;
    }

    return courses.filter((course) => String(course.department_name || '').trim() === normalizedDepartment);
}

function formatCourseOptionLabel(course) {
    const name = String(course.name || '').trim();
    const code = String(course.course_code || '').trim();
    if (!code) {
        return name;
    }

    return `${name} (${code})`;
}

function refreshStudentCourseOptions(departmentSelectId, courseSelectId, selectedCourse = '') {
    const departmentSelect = document.getElementById(departmentSelectId);
    const courseSelect = document.getElementById(courseSelectId);

    if (!courseSelect) {
        return;
    }

    const departmentName = departmentSelect ? departmentSelect.value : '';
    const matchingCourses = getCoursesForDepartmentName(departmentName);
    const previousValue = String(selectedCourse || courseSelect.value || '').trim();

    courseSelect.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Select course';
    courseSelect.appendChild(placeholderOption);

    matchingCourses.forEach((course) => {
        const option = document.createElement('option');
        option.value = String(course.name || '').trim();
        option.textContent = formatCourseOptionLabel(course);
        courseSelect.appendChild(option);
    });

    if (!previousValue) {
        courseSelect.value = '';
        return;
    }

    const hasMatch = matchingCourses.some((course) => String(course.name || '').trim() === previousValue);
    courseSelect.value = hasMatch ? previousValue : '';
}

function getOrdinalYear(value) {
    if (value === 1) {
        return '1st';
    }

    if (value === 2) {
        return '2nd';
    }

    if (value === 3) {
        return '3rd';
    }

    return `${value}th`;
}

function refreshYearLevelOptionsFromCourse(courseSelectId, yearLevelSelectId, selectedYear = '') {
    const courseSelect = document.getElementById(courseSelectId);
    const yearLevelSelect = document.getElementById(yearLevelSelectId);

    if (!courseSelect || !yearLevelSelect) {
        return;
    }

    const selectedCourse = getCourseByName(courseSelect.value);
    const parsedYears = Number.parseInt(selectedCourse?.total_years, 10);
    const hasSelectedCourse = Boolean(selectedCourse);
    const maxYears = !hasSelectedCourse
        ? 0
        : (Number.isInteger(parsedYears) && parsedYears > 0 ? parsedYears : 4);
    const previousValue = String(selectedYear || yearLevelSelect.value || '').trim();

    yearLevelSelect.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Select year level';
    yearLevelSelect.appendChild(placeholderOption);

    for (let year = 1; year <= maxYears; year += 1) {
        const value = `${getOrdinalYear(year)} Year`;
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        yearLevelSelect.appendChild(option);
    }

    if (previousValue) {
        const exists = Array.from(yearLevelSelect.options).some((option) => option.value === previousValue);
        yearLevelSelect.value = exists ? previousValue : '';
    } else {
        yearLevelSelect.value = '';
    }
}

function populateCatalogSelects(options) {
    populateSelectOptions('newProfessorDesignation', options.designations, 'Select designation');
    populateSelectOptions('editProfessorDesignation', options.designations, 'Select designation');

    populateSelectOptions('newProfessorDepartment', options.departments, 'Select department');
    populateSelectOptions('newStudentDepartment', options.departments, 'Select department');
    populateSelectOptions('editProfessorDepartment', options.departments, 'Select department');
    populateSelectOptions('editStudentDepartment', options.departments, 'Select department');

    refreshStudentCourseOptions('newStudentDepartment', 'newStudentCourse');
    refreshStudentCourseOptions('editStudentDepartment', 'editStudentCourse');
    refreshYearLevelOptionsFromCourse('newStudentCourse', 'newStudentYearLevel');
    refreshYearLevelOptionsFromCourse('editStudentCourse', 'editStudentYearLevel');

    populateSelectOptions('newProfessorSubject', options.subjects, 'Select subject');
    populateSelectOptions('newStudentSubject', options.subjects, 'Select subject');
    populateSelectOptions('editProfessorSubject', options.subjects, 'Select subject');
    populateSelectOptions('editStudentSubject', options.subjects, 'Select subject');
}

function populateSelectOptions(selectId, values, placeholder) {
    const select = document.getElementById(selectId);
    if (!select) {
        return;
    }

    const previousValue = select.value;
    const normalized = (values || []).map(value => String(value).trim()).filter(Boolean);
    const uniqueValues = Array.from(new Set(normalized));

    select.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.appendChild(placeholderOption);

    uniqueValues.forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    });

    if (previousValue) {
        setSelectValueWithFallback(selectId, previousValue);
    }
}

function setSelectValueWithFallback(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) {
        return;
    }

    const normalized = String(value || '').trim();
    if (!normalized) {
        select.value = '';
        return;
    }

    const exists = Array.from(select.options).some(option => option.value === normalized);
    if (!exists) {
        const option = document.createElement('option');
        option.value = normalized;
        option.textContent = normalized;
        select.appendChild(option);
    }

    select.value = normalized;
}

function getNextUserCode(role) {
    const year = new Date().getFullYear();
    const prefix = role === 'professor' ? 'P' : 'S';
    const pattern = new RegExp(`^${prefix}${year}\\s(\\d{5})$`);

    let max = 0;
    API.users.forEach(user => {
        if (user.user_type !== role || !user.user_code) return;
        const match = user.user_code.match(pattern);
        if (match) {
            max = Math.max(max, parseInt(match[1], 10));
        }
    });

    return `${prefix}${year} ${String(max + 1).padStart(5, '0')}`;
}

function updateGeneratedUserCode() {
    const roleSelect = document.getElementById('newUserRole');
    const codeInput = document.getElementById('newUserCode');
    if (!roleSelect || !codeInput) return;
    codeInput.value = getNextUserCode(roleSelect.value);
}

function updateRoleSpecificFields() {
    const roleSelect = document.getElementById('newUserRole');
    const professorFields = document.getElementById('professorFields');
    const studentFields = document.getElementById('studentFields');
    if (!roleSelect || !professorFields || !studentFields) return;

    if (roleSelect.value === 'professor') {
        professorFields.classList.remove('hidden');
        studentFields.classList.add('hidden');
    } else {
        studentFields.classList.remove('hidden');
        professorFields.classList.add('hidden');
    }
}

function updateEditRoleSpecificFields() {
    const roleSelect = document.getElementById('editUserRole');
    const professorFields = document.getElementById('editProfessorFields');
    const studentFields = document.getElementById('editStudentFields');
    if (!roleSelect || !professorFields || !studentFields) return;

    if (roleSelect.value === 'professor') {
        professorFields.classList.remove('hidden');
        studentFields.classList.add('hidden');
    } else {
        studentFields.classList.remove('hidden');
        professorFields.classList.add('hidden');
    }
}

function handleAddUserSubmit(event) {
    event.preventDefault();

    const role = document.getElementById('newUserRole').value;
    const name = document.getElementById('newUserName').value.trim();
    const birthday = document.getElementById('newUserBirthday').value;
    const email = document.getElementById('newUserEmail').value.trim();
    const contact = document.getElementById('newUserContact').value.trim();
    const password = document.getElementById('newUserPassword').value;
    const confirmPassword = document.getElementById('newUserConfirmPassword').value;

    if (password !== confirmPassword) {
        alert('Password and Confirm Password do not match.');
        return;
    }

    const payload = {
        name: name,
        birthday: birthday,
        email: email,
        contact: contact,
        password: password,
        password_confirmation: confirmPassword,
        user_type: role,
    };

    if (role === 'professor') {
        payload.designation = document.getElementById('newProfessorDesignation').value;
        payload.department = document.getElementById('newProfessorDepartment').value;
    } else {
        payload.department = document.getElementById('newStudentDepartment').value;
        payload.course = document.getElementById('newStudentCourse').value;
        payload.year_level = document.getElementById('newStudentYearLevel').value;
    }

    const token = localStorage.getItem('api_token');

    fetch('/api/user-management/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
    })
    .then(async (response) => {
        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                const firstField = Object.keys(data.errors)[0];
                const firstMessage = data.errors[firstField][0];
                throw new Error(firstMessage);
            }
            throw new Error(data.message || 'Failed to create user');
        }

        alert('User created successfully');
        closeAddUserModal();
        API.loadUsers();
    })
    .catch((error) => {
        alert(error.message || 'Error creating user');
    });
}

function handleEditUserSubmit(event) {
    event.preventDefault();

    const userId = document.getElementById('editUserId').value;
    const role = document.getElementById('editUserRole').value;

    const payload = {
        name: document.getElementById('editUserName').value.trim(),
        birthday: document.getElementById('editUserBirthday').value,
        email: document.getElementById('editUserEmail').value.trim(),
        contact: document.getElementById('editUserContact').value.trim(),
        user_type: role,
    };

    if (role === 'professor') {
        payload.designation = document.getElementById('editProfessorDesignation').value;
        payload.department = document.getElementById('editProfessorDepartment').value;
        payload.subject = document.getElementById('editProfessorSubject').value;
        payload.course = null;
        payload.year_level = null;
    } else {
        payload.department = document.getElementById('editStudentDepartment').value;
        payload.course = document.getElementById('editStudentCourse').value;
        payload.subject = document.getElementById('editStudentSubject').value;
        payload.year_level = document.getElementById('editStudentYearLevel').value;
        payload.designation = null;
    }

    const token = localStorage.getItem('api_token');

    fetch(`/api/user-management/users/${userId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
    })
    .then(async (response) => {
        const data = await response.json();
        if (!response.ok) {
            if (data.errors) {
                const firstField = Object.keys(data.errors)[0];
                throw new Error(data.errors[firstField][0]);
            }
            throw new Error(data.message || 'Failed to update user');
        }

        alert('User updated successfully');
        closeEditUserModal();
        API.loadUsers();
    })
    .catch((error) => {
        alert(error.message || 'Error updating user');
    });
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
    .then(response => {
        return response.json().then(data => {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        });
    })
    .catch(error => {
        console.error('Logout error:', error);
        localStorage.removeItem('api_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    });
}

// Expose handlers for inline attributes in Blade templates.
window.filterUsers = filterUsers;
window.setUserRoleFilter = setUserRoleFilter;
window.changePage = changePage;
window.toggleUserSelection = toggleUserSelection;
window.toggleSelectAll = toggleSelectAll;
window.deleteSelectedUsers = deleteSelectedUsers;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.openAddUserModal = openAddUserModal;
window.closeAddUserModal = closeAddUserModal;
window.closeEditUserModal = closeEditUserModal;
window.logout = logout;

document.addEventListener('DOMContentLoaded', () => {
    API.init();
    initializeSidebarNavigation();

    const roleSelect = document.getElementById('newUserRole');
    const editRoleSelect = document.getElementById('editUserRole');
    const newStudentDepartment = document.getElementById('newStudentDepartment');
    const newStudentCourse = document.getElementById('newStudentCourse');
    const editStudentDepartment = document.getElementById('editStudentDepartment');
    const editStudentCourse = document.getElementById('editStudentCourse');
    const addUserForm = document.getElementById('addUserForm');
    const editUserForm = document.getElementById('editUserForm');
    const modal = document.getElementById('addUserModal');
    const editModal = document.getElementById('editUserModal');

    if (roleSelect) {
        roleSelect.addEventListener('change', () => {
            updateRoleSpecificFields();
            updateGeneratedUserCode();
        });
    }

    if (newStudentDepartment) {
        newStudentDepartment.addEventListener('change', () => {
            refreshStudentCourseOptions('newStudentDepartment', 'newStudentCourse');
            refreshYearLevelOptionsFromCourse('newStudentCourse', 'newStudentYearLevel');
        });
    }

    if (newStudentCourse) {
        newStudentCourse.addEventListener('change', () => {
            refreshYearLevelOptionsFromCourse('newStudentCourse', 'newStudentYearLevel');
        });
    }

    if (editStudentDepartment) {
        editStudentDepartment.addEventListener('change', () => {
            refreshStudentCourseOptions('editStudentDepartment', 'editStudentCourse');
            refreshYearLevelOptionsFromCourse('editStudentCourse', 'editStudentYearLevel');
        });
    }

    if (editStudentCourse) {
        editStudentCourse.addEventListener('change', () => {
            refreshYearLevelOptionsFromCourse('editStudentCourse', 'editStudentYearLevel');
        });
    }

    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', updateEditRoleSpecificFields);
    }

    if (addUserForm) {
        addUserForm.addEventListener('submit', handleAddUserSubmit);
    }

    if (editUserForm) {
        editUserForm.addEventListener('submit', handleEditUserSubmit);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAddUserModal();
            }
        });
    }

    if (editModal) {
        editModal.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditUserModal();
            }
        });
    }
});
