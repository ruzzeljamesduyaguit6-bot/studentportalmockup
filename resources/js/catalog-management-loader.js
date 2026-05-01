const CatalogAPI = {
    user: null,
    type: '',
    label: '',
    currentPage: 1,
    totalPages: 1,
    pageSize: 15,
    searchTerm: '',
    departmentFilter: 0,
    subjectCourses: [],
    courseSubjects: [],
    departmentCourseOptions: [],
    departmentOptions: [],
    catalogOptions: null,

    async init() {
        const body = document.body;
        this.type = body.dataset.catalogType || '';
        this.label = body.dataset.catalogLabel || 'Item';

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

        this.renderNavbar();
        this.filterSidebarByRole();

        await this.loadCatalogOptions();

        if (this.type === 'departments') {
            await this.loadDepartmentCourseOptions();
        }

        await this.loadPage(1);
    },

    async loadCatalogOptions() {
        const data = await this.request('/api/catalog/options');
        this.catalogOptions = data;

        this.departmentOptions = Array.isArray(data.department_options)
            ? data.department_options
            : [];

        this.departmentCourseOptions = Array.isArray(data.course_options)
            ? data.course_options
            : [];
    },

    async loadSubjectCourses(departmentId = 0) {
        const params = new URLSearchParams();
        if (departmentId > 0) {
            params.set('department_id', String(departmentId));
        }

        const url = params.toString()
            ? `/api/catalog/subjects/courses?${params.toString()}`
            : '/api/catalog/subjects/courses';

        const data = await this.request(url);
        this.subjectCourses = data.courses || [];

        return this.subjectCourses;
    },

    async loadCourseSubjects(departmentId = 0) {
        const params = new URLSearchParams();
        if (departmentId > 0) {
            params.set('department_id', String(departmentId));
        }

        const url = params.toString()
            ? `/api/catalog/courses/subjects?${params.toString()}`
            : '/api/catalog/courses/subjects';

        const data = await this.request(url);
        this.courseSubjects = data.subjects || [];

        return this.courseSubjects;
    },

    async loadDepartmentCourseOptions() {
        if (this.departmentCourseOptions.length > 0) {
            return this.departmentCourseOptions;
        }

        const data = await this.request('/api/catalog/subjects/courses');
        this.departmentCourseOptions = data.courses || [];

        return this.departmentCourseOptions;
    },

    renderSubjectCourseChecklist(selectedCourseIds = [], targetId = 'subjectCoursesChecklist') {
        const list = document.getElementById(targetId);
        if (!list) {
            return;
        }

        const selectedSet = new Set((selectedCourseIds || []).map((value) => Number.parseInt(value, 10)));
        const fieldName = targetId === 'editSubjectCoursesChecklist'
            ? 'editSubjectCourseIds[]'
            : 'subjectCourseIds[]';

        if (!this.subjectCourses.length) {
            list.innerHTML = '<p class="catalog-checkbox-empty">No courses found for the selected scope.</p>';
            return;
        }

        list.innerHTML = this.subjectCourses.map((course) => {
            const checked = selectedSet.has(Number(course.id)) ? 'checked' : '';
            const code = String(course.course_code || '').trim();
            const tag = code ? ` (${this.escapeHTML(code)})` : '';

            return `
                <label class="catalog-checkbox-item">
                    <input type="checkbox" name="${fieldName}" value="${course.id}" ${checked}>
                    <span>${this.escapeHTML(course.name)}${tag}</span>
                </label>
            `;
        }).join('');
    },

    renderCourseSubjectChecklist(selectedSubjectIds = [], targetId = 'courseSubjectsChecklist') {
        const list = document.getElementById(targetId);
        if (!list) {
            return;
        }

        const selectedSet = new Set((selectedSubjectIds || []).map((value) => Number.parseInt(value, 10)));
        const fieldName = targetId === 'courseAddSubjectsChecklist'
            ? 'courseAddSubjectIds[]'
            : 'courseSubjectIds[]';

        if (!this.courseSubjects.length) {
            list.innerHTML = '<p class="catalog-checkbox-empty">No subjects found for the selected department.</p>';
            return;
        }

        list.innerHTML = this.courseSubjects.map((subject) => {
            const checked = selectedSet.has(Number(subject.id)) ? 'checked' : '';
            const units = Number.parseInt(subject.units, 10);
            const unitsText = Number.isInteger(units) ? ` (${units} unit${units === 1 ? '' : 's'})` : '';
            const code = String(subject.subject_code || '').trim();
            const codeText = code ? `${this.escapeHTML(code)} - ` : '';
            const freeTag = subject.is_free_for_all ? ' [Free for all]' : '';

            return `
                <label class="catalog-checkbox-item">
                    <input type="checkbox" name="${fieldName}" value="${subject.id}" ${checked}>
                    <span>${codeText}${this.escapeHTML(subject.name)}${unitsText}${freeTag}</span>
                </label>
            `;
        }).join('');
    },

    renderDepartmentCourseChecklist(selectedCourseIds = [], targetId = 'departmentCoursesChecklist') {
        const list = document.getElementById(targetId);
        if (!list) {
            return;
        }

        const selectedSet = new Set((selectedCourseIds || []).map((value) => Number.parseInt(value, 10)));
        const fieldName = targetId === 'editDepartmentCoursesChecklist'
            ? 'editDepartmentCourseIds[]'
            : 'departmentCourseIds[]';

        if (!this.departmentCourseOptions.length) {
            list.innerHTML = '<p class="catalog-checkbox-empty">No courses found.</p>';
            return;
        }

        list.innerHTML = this.departmentCourseOptions.map((course) => {
            const checked = selectedSet.has(Number(course.id)) ? 'checked' : '';
            const code = String(course.course_code || '').trim();
            const codeTag = code ? ` (${this.escapeHTML(code)})` : '';

            return `
                <label class="catalog-checkbox-item">
                    <input type="checkbox" name="${fieldName}" value="${course.id}" ${checked}>
                    <span>${this.escapeHTML(course.name)}${codeTag}</span>
                </label>
            `;
        }).join('');
    },

    renderDepartmentSelect(targetId, includePlaceholder = true) {
        const select = document.getElementById(targetId);
        if (!select) {
            return;
        }

        const previousValue = select.value;
        const departments = Array.isArray(this.departmentOptions) ? this.departmentOptions : [];

        select.innerHTML = '';

        if (includePlaceholder) {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select department';
            select.appendChild(placeholder);
        }

        departments.forEach((department) => {
            const option = document.createElement('option');
            option.value = String(department.id);
            option.textContent = String(department.name || '');
            select.appendChild(option);
        });

        if (previousValue) {
            const exists = Array.from(select.options).some((option) => option.value === previousValue);
            select.value = exists ? previousValue : '';
        }
    },

    renderDegreeLevelSelect(targetId) {
        const select = document.getElementById(targetId);
        if (!select) {
            return;
        }

        const previousValue = select.value;
        const degreeLevels = Array.isArray(this.catalogOptions?.degree_levels)
            ? this.catalogOptions.degree_levels
            : [];

        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select degree level';
        select.appendChild(placeholder);

        degreeLevels.forEach((level) => {
            const option = document.createElement('option');
            option.value = String(level.value || '');
            option.textContent = String(level.label || '');
            select.appendChild(option);
        });

        if (previousValue) {
            const exists = Array.from(select.options).some((option) => option.value === previousValue);
            select.value = exists ? previousValue : '';
        }
    },

    renderNavbar() {
        const userName = document.getElementById('userName');
        const userInitials = document.getElementById('userInitials');
        const navbar = document.getElementById('navbar');

        if (userName) {
            userName.textContent = this.user.name;
        }

        const parts = this.user.name.trim().split(' ');
        const initials = parts.length >= 2
            ? (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
            : parts[0].charAt(0).toUpperCase();

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

        navItems.forEach((item) => {
            const allowedRoles = item.getAttribute('data-roles');
            if (!allowedRoles) {
                return;
            }
            const rolesArray = allowedRoles.split(',').map((value) => value.trim());
            item.style.display = rolesArray.includes(userRole) ? 'flex' : 'none';
        });
    },

    async loadPage(page) {
        const targetPage = Math.max(1, page);
        const params = new URLSearchParams({
            page: String(targetPage),
            perPage: String(this.pageSize),
        });

        if (this.searchTerm.trim()) {
            params.set('search', this.searchTerm.trim());
        }

        if (['courses', 'subjects'].includes(this.type) && this.departmentFilter > 0) {
            params.set('department_id', String(this.departmentFilter));
        }

        const data = await this.request(`/api/catalog/${this.type}?${params.toString()}`);

        this.currentPage = data.page || 1;
        this.totalPages = data.totalPages || 1;

        if (Array.isArray(data.departmentOptions)) {
            this.departmentOptions = data.departmentOptions;
        }

        if (['courses', 'subjects'].includes(this.type)) {
            this.renderDepartmentFilter();
            if (this.type === 'courses') {
                this.renderDepartmentSelect('courseDepartmentInput', true);
            }
            if (this.type === 'subjects') {
                this.renderDepartmentSelect('subjectDepartmentInput', true);
                this.renderDepartmentSelect('editSubjectDepartmentInput', true);
            }
        }

        this.renderStats(data.total || 0);
        this.renderTable(data.items || []);
        this.renderPagination();
    },

    renderDepartmentFilter() {
        const select = document.getElementById('catalogDepartmentFilter');
        if (!select) {
            return;
        }

        const departments = Array.isArray(this.departmentOptions) ? this.departmentOptions : [];
        select.innerHTML = '';

        const allOption = document.createElement('option');
        allOption.value = '0';
        allOption.textContent = 'All Departments';
        select.appendChild(allOption);

        departments.forEach((department) => {
            const option = document.createElement('option');
            option.value = String(department.id);
            option.textContent = String(department.name || '');
            select.appendChild(option);
        });

        select.value = String(this.departmentFilter || 0);
    },

    renderStats(total) {
        const stats = document.getElementById('catalogStats');
        if (!stats) {
            return;
        }

        stats.innerHTML = `
            <div class="count-card">
                <div class="count-label">Total ${this.label}s</div>
                <div class="count-value">${total}</div>
            </div>
        `;
    },

    renderTable(items) {
        const tbody = document.getElementById('catalogTableBody');
        if (!tbody) {
            return;
        }

        const emptyColSpan = this.type === 'courses'
            ? 9
            : (this.type === 'subjects' ? 7 : (this.type === 'departments' ? 4 : 2));

        if (!items.length) {
            tbody.innerHTML = `<tr><td colspan="${emptyColSpan}" class="loading">No records found</td></tr>`;
            return;
        }

        if (this.type === 'courses') {
            tbody.innerHTML = items.map((item) => {
                const yearsText = item.total_years ? `${item.total_years}` : '-';

                return `
                    <tr>
                        <td>${item.id}</td>
                        <td>${this.escapeHTML(item.course_code || '-')}</td>
                        <td>${this.escapeHTML(item.name || '')}</td>
                        <td>${this.escapeHTML(this.formatDegreeLabel(item.degree_level))}</td>
                        <td>${this.escapeHTML(item.department_name || 'Unassigned')}</td>
                        <td>${yearsText}</td>
                        <td>${item.subjects_count ?? 0}</td>
                        <td>${item.total_units ?? 0}</td>
                        <td class="table-action-right">
                            <div class="user-actions">
                                <button class="action-btn" onclick="openEditCatalogModal(${item.id}, '${this.escapeJS(item.name)}')">Edit</button>
                                <button class="action-btn delete" onclick="deleteCatalogItem(${item.id}, '${this.escapeJS(item.name)}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            return;
        }

        if (this.type === 'subjects') {
            tbody.innerHTML = items.map((item) => {
                const freeForAllText = item.is_free_for_all ? 'Yes' : 'No';
                const departmentName = item.is_free_for_all ? 'All Departments' : (item.department_name || 'Unassigned');

                return `
                    <tr>
                        <td>${item.id}</td>
                        <td>${this.escapeHTML(item.subject_code || '-')}</td>
                        <td>${this.escapeHTML(item.name || '')}</td>
                        <td>${this.escapeHTML(departmentName)}</td>
                        <td>${item.units ?? '-'}</td>
                        <td>${freeForAllText}</td>
                        <td class="table-action-right">
                            <div class="user-actions">
                                <button class="action-btn" onclick="openEditCatalogModal(${item.id}, '${this.escapeJS(item.name)}')">Edit</button>
                                <button class="action-btn delete" onclick="deleteCatalogItem(${item.id}, '${this.escapeJS(item.name)}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            return;
        }

        if (this.type === 'departments') {
            tbody.innerHTML = items.map((item) => `
                <tr>
                    <td>${item.id}</td>
                    <td>${this.escapeHTML(item.name || '')}</td>
                    <td>${item.courses_count ?? 0}</td>
                    <td class="table-action-right">
                        <div class="user-actions">
                            <button class="action-btn" onclick="openEditCatalogModal(${item.id}, '${this.escapeJS(item.name)}')">Edit</button>
                            <button class="action-btn delete" onclick="deleteCatalogItem(${item.id}, '${this.escapeJS(item.name)}')">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
            return;
        }

        tbody.innerHTML = items.map((item) => `
            <tr>
                <td>${this.escapeHTML(item.name || '')}</td>
                <td class="table-action-right">
                    <button class="action-btn delete" onclick="deleteCatalogItem(${item.id}, '${this.escapeJS(item.name)}')">Delete</button>
                </td>
            </tr>
        `).join('');
    },

    renderPagination() {
        const info = document.getElementById('catalogPaginationInfo');
        const prev = document.getElementById('catalogPrevPageBtn');
        const next = document.getElementById('catalogNextPageBtn');

        if (info) {
            info.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        }
        if (prev) {
            prev.disabled = this.currentPage <= 1;
        }
        if (next) {
            next.disabled = this.currentPage >= this.totalPages;
        }
    },

    async setDepartmentFilter(departmentId) {
        this.departmentFilter = Math.max(0, Number.parseInt(departmentId, 10) || 0);
        await this.loadPage(1);
    },

    async addItem(payload) {
        await this.request(`/api/catalog/${this.type}`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        await this.loadPage(1);
    },

    async deleteItem(id) {
        await this.request(`/api/catalog/${this.type}/${id}`, {
            method: 'DELETE',
        });

        await this.loadPage(this.currentPage);
    },

    formatDegreeLabel(value) {
        const normalized = String(value || '').trim();
        if (!normalized) {
            return '-';
        }

        const match = (this.catalogOptions?.degree_levels || []).find((level) => level.value === normalized);
        return match ? match.label : normalized;
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

        if (!response.ok || data.success === false) {
            if (data.errors) {
                const firstField = Object.keys(data.errors)[0];
                throw new Error(data.errors[firstField][0]);
            }
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

    escapeJS(value) {
        return String(value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'");
    },
};

function getCheckedIntegerValues(selector) {
    return Array.from(document.querySelectorAll(selector))
        .map((element) => Number.parseInt(element.value, 10))
        .filter((value) => Number.isInteger(value));
}

function getDegreePrefix(value) {
    const map = {
        associate: 'AS',
        bachelor: 'BS',
        master: 'MS',
        doctorate: 'PHD',
        certificate: 'CERT',
        diploma: 'DIP',
    };

    return map[String(value || '').trim()] || '';
}

function buildProgramInitials(program) {
    const letters = String(program || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return letters || 'GEN';
}

function updateCourseAutoFill() {
    if (CatalogAPI.type !== 'courses') {
        return;
    }

    const degreeSelect = document.getElementById('courseDegreeLevelInput');
    const programInput = document.getElementById('courseProgramInput');
    const nameInput = document.getElementById('catalogNameInput');
    const codeInput = document.getElementById('courseCodeInput');

    if (!degreeSelect || !programInput || !nameInput || !codeInput) {
        return;
    }

    const degreeValue = String(degreeSelect.value || '').trim();
    const programTitle = String(programInput.value || '').trim();
    const prefix = getDegreePrefix(degreeValue);

    if (!prefix || !programTitle) {
        return;
    }

    nameInput.value = `${prefix} ${programTitle}`.trim();
    codeInput.value = `${prefix}-${buildProgramInitials(programTitle)}`;
}

async function handleCourseDepartmentSelectionChange() {
    const departmentSelect = document.getElementById('courseDepartmentInput');
    if (!departmentSelect) {
        return;
    }

    const departmentId = Number.parseInt(departmentSelect.value, 10);
    if (!Number.isInteger(departmentId) || departmentId <= 0) {
        CatalogAPI.courseSubjects = [];
        CatalogAPI.renderCourseSubjectChecklist([], 'courseAddSubjectsChecklist');
        return;
    }

    await CatalogAPI.loadCourseSubjects(departmentId);
    CatalogAPI.renderCourseSubjectChecklist([], 'courseAddSubjectsChecklist');
}

async function handleSubjectAddScopeChange() {
    const freeForAllCheckbox = document.getElementById('subjectFreeForAllInput');
    const departmentSelect = document.getElementById('subjectDepartmentInput');

    if (!freeForAllCheckbox || !departmentSelect) {
        return;
    }

    departmentSelect.disabled = freeForAllCheckbox.checked;

    if (freeForAllCheckbox.checked) {
        await CatalogAPI.loadSubjectCourses(0);
        CatalogAPI.renderSubjectCourseChecklist([], 'subjectCoursesChecklist');
        return;
    }

    const departmentId = Number.parseInt(departmentSelect.value, 10);
    if (!Number.isInteger(departmentId) || departmentId <= 0) {
        CatalogAPI.subjectCourses = [];
        CatalogAPI.renderSubjectCourseChecklist([], 'subjectCoursesChecklist');
        return;
    }

    await CatalogAPI.loadSubjectCourses(departmentId);
    CatalogAPI.renderSubjectCourseChecklist([], 'subjectCoursesChecklist');
}

async function handleSubjectEditScopeChange() {
    const freeForAllCheckbox = document.getElementById('editSubjectFreeForAllInput');
    const departmentSelect = document.getElementById('editSubjectDepartmentInput');

    if (!freeForAllCheckbox || !departmentSelect) {
        return;
    }

    const selectedCourseIds = getCheckedIntegerValues('input[name="editSubjectCourseIds[]"]:checked');
    departmentSelect.disabled = freeForAllCheckbox.checked;

    if (freeForAllCheckbox.checked) {
        await CatalogAPI.loadSubjectCourses(0);
        CatalogAPI.renderSubjectCourseChecklist(selectedCourseIds, 'editSubjectCoursesChecklist');
        return;
    }

    const departmentId = Number.parseInt(departmentSelect.value, 10);
    if (!Number.isInteger(departmentId) || departmentId <= 0) {
        CatalogAPI.subjectCourses = [];
        CatalogAPI.renderSubjectCourseChecklist([], 'editSubjectCoursesChecklist');
        return;
    }

    await CatalogAPI.loadSubjectCourses(departmentId);
    CatalogAPI.renderSubjectCourseChecklist(selectedCourseIds, 'editSubjectCoursesChecklist');
}

async function openAddCatalogModal() {
    const modal = document.getElementById('addCatalogModal');
    const form = document.getElementById('addCatalogForm');
    if (!modal) {
        return;
    }

    if (form) {
        form.reset();
    }

    if (CatalogAPI.type === 'courses') {
        CatalogAPI.renderDegreeLevelSelect('courseDegreeLevelInput');
        CatalogAPI.renderDepartmentSelect('courseDepartmentInput', true);
        CatalogAPI.courseSubjects = [];
        CatalogAPI.renderCourseSubjectChecklist([], 'courseAddSubjectsChecklist');
    }

    if (CatalogAPI.type === 'subjects') {
        CatalogAPI.renderDepartmentSelect('subjectDepartmentInput', true);
        const freeForAllCheckbox = document.getElementById('subjectFreeForAllInput');
        if (freeForAllCheckbox) {
            freeForAllCheckbox.checked = false;
        }
        CatalogAPI.subjectCourses = [];
        CatalogAPI.renderSubjectCourseChecklist([], 'subjectCoursesChecklist');
    }

    if (CatalogAPI.type === 'departments') {
        await CatalogAPI.loadDepartmentCourseOptions();
        CatalogAPI.renderDepartmentCourseChecklist([], 'departmentCoursesChecklist');
    }

    modal.classList.remove('hidden');
}

function closeAddCatalogModal() {
    const modal = document.getElementById('addCatalogModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

async function openEditCatalogModal(id, name) {
    const modal = document.getElementById('editCatalogModal');
    const title = document.getElementById('editCatalogModalTitle');
    const idInput = document.getElementById('editCatalogId');

    if (!modal || !idInput) {
        return;
    }

    try {
        idInput.value = String(id);

        if (CatalogAPI.type === 'courses') {
            const data = await CatalogAPI.request(`/api/catalog/courses/${id}/subjects`);
            const course = data.course || {};
            const departmentId = Number.parseInt(course.department_id, 10) || 0;

            await CatalogAPI.loadCourseSubjects(departmentId);
            CatalogAPI.renderCourseSubjectChecklist(data.subject_ids || [], 'courseSubjectsChecklist');

            if (title) {
                title.textContent = `Edit Course Subjects: ${name}`;
            }
        }

        if (CatalogAPI.type === 'subjects') {
            const data = await CatalogAPI.request(`/api/catalog/subjects/${id}/courses`);
            const subject = data.subject || {};

            const editNameInput = document.getElementById('editSubjectNameInput');
            const editCodeInput = document.getElementById('editSubjectCodeInput');
            const editUnitsInput = document.getElementById('editSubjectUnitsInput');
            const editDepartmentInput = document.getElementById('editSubjectDepartmentInput');
            const editFreeForAllCheckbox = document.getElementById('editSubjectFreeForAllInput');

            CatalogAPI.renderDepartmentSelect('editSubjectDepartmentInput', true);

            if (editNameInput) {
                editNameInput.value = subject.name || '';
            }
            if (editCodeInput) {
                editCodeInput.value = subject.subject_code || '';
            }
            if (editUnitsInput) {
                editUnitsInput.value = subject.units || '';
            }
            if (editDepartmentInput) {
                editDepartmentInput.value = subject.department_id ? String(subject.department_id) : '';
            }
            if (editFreeForAllCheckbox) {
                editFreeForAllCheckbox.checked = Boolean(subject.is_free_for_all);
            }

            const departmentId = Boolean(subject.is_free_for_all)
                ? 0
                : (Number.parseInt(subject.department_id, 10) || 0);

            await CatalogAPI.loadSubjectCourses(departmentId);
            CatalogAPI.renderSubjectCourseChecklist(data.course_ids || [], 'editSubjectCoursesChecklist');
            await handleSubjectEditScopeChange();

            if (title) {
                title.textContent = `Edit Subject: ${name}`;
            }
        }

        if (CatalogAPI.type === 'departments') {
            const data = await CatalogAPI.request(`/api/catalog/departments/${id}/courses`);

            const editDepartmentNameInput = document.getElementById('editDepartmentNameInput');
            if (editDepartmentNameInput) {
                editDepartmentNameInput.value = data.department?.name || name;
            }

            await CatalogAPI.loadDepartmentCourseOptions();
            CatalogAPI.renderDepartmentCourseChecklist(data.course_ids || [], 'editDepartmentCoursesChecklist');

            if (title) {
                title.textContent = `Edit Department: ${name}`;
            }
        }

        modal.classList.remove('hidden');
    } catch (error) {
        alert(error.message || `Failed to load ${CatalogAPI.label.toLowerCase()} details`);
    }
}

function closeEditCatalogModal() {
    const modal = document.getElementById('editCatalogModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function changeCatalogPage(delta) {
    const nextPage = CatalogAPI.currentPage + delta;
    if (nextPage < 1 || nextPage > CatalogAPI.totalPages) {
        return;
    }

    CatalogAPI.loadPage(nextPage);
}

function filterCatalogItems() {
    const input = document.getElementById('catalogSearchInput');
    CatalogAPI.searchTerm = input ? input.value : '';
    CatalogAPI.loadPage(1);
}

function applyDepartmentFilter() {
    const select = document.getElementById('catalogDepartmentFilter');
    if (!select) {
        return;
    }

    CatalogAPI.setDepartmentFilter(Number.parseInt(select.value, 10) || 0);
}

function clearDepartmentFilter() {
    const select = document.getElementById('catalogDepartmentFilter');
    if (select) {
        select.value = '0';
    }

    CatalogAPI.setDepartmentFilter(0);
}

async function deleteCatalogItem(id, name) {
    if (!confirm(`Delete ${CatalogAPI.label} "${name}"?`)) {
        return;
    }

    try {
        await CatalogAPI.deleteItem(id);
    } catch (error) {
        alert(error.message || `Failed to delete ${CatalogAPI.label.toLowerCase()}`);
    }
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

window.openAddCatalogModal = openAddCatalogModal;
window.closeAddCatalogModal = closeAddCatalogModal;
window.openEditCatalogModal = openEditCatalogModal;
window.closeEditCatalogModal = closeEditCatalogModal;
window.changeCatalogPage = changeCatalogPage;
window.filterCatalogItems = filterCatalogItems;
window.applyDepartmentFilter = applyDepartmentFilter;
window.clearDepartmentFilter = clearDepartmentFilter;
window.deleteCatalogItem = deleteCatalogItem;
window.logout = logout;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await CatalogAPI.init();

        const form = document.getElementById('addCatalogForm');
        const modal = document.getElementById('addCatalogModal');
        const editForm = document.getElementById('editCatalogForm');
        const editModal = document.getElementById('editCatalogModal');

        const courseDegreeInput = document.getElementById('courseDegreeLevelInput');
        const courseProgramInput = document.getElementById('courseProgramInput');
        const courseDepartmentInput = document.getElementById('courseDepartmentInput');
        const courseCodeInput = document.getElementById('courseCodeInput');

        const subjectDepartmentInput = document.getElementById('subjectDepartmentInput');
        const subjectFreeForAllInput = document.getElementById('subjectFreeForAllInput');
        const subjectCodeInput = document.getElementById('subjectCodeInput');

        const editSubjectDepartmentInput = document.getElementById('editSubjectDepartmentInput');
        const editSubjectFreeForAllInput = document.getElementById('editSubjectFreeForAllInput');
        const editSubjectCodeInput = document.getElementById('editSubjectCodeInput');

        if (courseDegreeInput) {
            courseDegreeInput.addEventListener('change', updateCourseAutoFill);
        }

        if (courseProgramInput) {
            courseProgramInput.addEventListener('input', updateCourseAutoFill);
        }

        if (courseDepartmentInput) {
            courseDepartmentInput.addEventListener('change', async () => {
                await handleCourseDepartmentSelectionChange();
            });
        }

        if (courseCodeInput) {
            courseCodeInput.addEventListener('input', () => {
                courseCodeInput.value = courseCodeInput.value.toUpperCase();
            });
        }

        if (subjectCodeInput) {
            subjectCodeInput.addEventListener('input', () => {
                subjectCodeInput.value = subjectCodeInput.value.toUpperCase();
            });
        }

        if (subjectDepartmentInput) {
            subjectDepartmentInput.addEventListener('change', async () => {
                await handleSubjectAddScopeChange();
            });
        }

        if (subjectFreeForAllInput) {
            subjectFreeForAllInput.addEventListener('change', async () => {
                await handleSubjectAddScopeChange();
            });
        }

        if (editSubjectCodeInput) {
            editSubjectCodeInput.addEventListener('input', () => {
                editSubjectCodeInput.value = editSubjectCodeInput.value.toUpperCase();
            });
        }

        if (editSubjectDepartmentInput) {
            editSubjectDepartmentInput.addEventListener('change', async () => {
                await handleSubjectEditScopeChange();
            });
        }

        if (editSubjectFreeForAllInput) {
            editSubjectFreeForAllInput.addEventListener('change', async () => {
                await handleSubjectEditScopeChange();
            });
        }

        if (form) {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const nameInput = document.getElementById('catalogNameInput');
                const name = nameInput ? nameInput.value.trim() : '';
                if (!name) {
                    return;
                }

                const payload = { name };

                if (CatalogAPI.type === 'courses') {
                    const courseCodeField = document.getElementById('courseCodeInput');
                    const courseDegreeField = document.getElementById('courseDegreeLevelInput');
                    const courseYearsField = document.getElementById('courseYearsInput');
                    const courseDepartmentField = document.getElementById('courseDepartmentInput');

                    const code = String(courseCodeField?.value || '').trim().toUpperCase();
                    const degree = String(courseDegreeField?.value || '').trim();
                    const years = Number.parseInt(courseYearsField?.value || '', 10);
                    const departmentId = Number.parseInt(courseDepartmentField?.value || '', 10);
                    const subjectIds = getCheckedIntegerValues('input[name="courseAddSubjectIds[]"]:checked');

                    if (!code) {
                        alert('Please enter a course code.');
                        return;
                    }

                    if (!degree) {
                        alert('Please select a degree level.');
                        return;
                    }

                    if (!Number.isInteger(years) || years < 1) {
                        alert('Please enter valid total years to finish.');
                        return;
                    }

                    if (!Number.isInteger(departmentId) || departmentId <= 0) {
                        alert('Please select a department.');
                        return;
                    }

                    payload.course_code = code;
                    payload.degree_level = degree;
                    payload.total_years = years;
                    payload.department_id = departmentId;
                    payload.subject_ids = subjectIds;
                }

                if (CatalogAPI.type === 'subjects') {
                    const unitsInput = document.getElementById('subjectUnitsInput');
                    const codeInput = document.getElementById('subjectCodeInput');
                    const departmentInput = document.getElementById('subjectDepartmentInput');
                    const freeForAllInput = document.getElementById('subjectFreeForAllInput');

                    const units = Number.parseInt(unitsInput?.value || '', 10);
                    const code = String(codeInput?.value || '').trim().toUpperCase();
                    const isFreeForAll = Boolean(freeForAllInput?.checked);
                    const departmentId = Number.parseInt(departmentInput?.value || '', 10);
                    const courseIds = getCheckedIntegerValues('input[name="subjectCourseIds[]"]:checked');

                    if (!code) {
                        alert('Please enter a subject code.');
                        return;
                    }

                    if (!Number.isInteger(units) || units < 1) {
                        alert('Please enter valid subject units.');
                        return;
                    }

                    if (!isFreeForAll && (!Number.isInteger(departmentId) || departmentId <= 0)) {
                        alert('Please select a department or enable Free for all departments.');
                        return;
                    }

                    payload.subject_code = code;
                    payload.units = units;
                    payload.is_free_for_all = isFreeForAll;
                    payload.department_id = isFreeForAll ? null : departmentId;
                    payload.course_ids = courseIds;
                }

                if (CatalogAPI.type === 'departments') {
                    const courseIds = getCheckedIntegerValues('input[name="departmentCourseIds[]"]:checked');
                    payload.course_ids = courseIds;
                }

                await CatalogAPI.addItem(payload);
                closeAddCatalogModal();
            });
        }

        if (editForm) {
            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const idInput = document.getElementById('editCatalogId');
                const itemId = idInput ? Number.parseInt(idInput.value, 10) : NaN;
                if (!Number.isInteger(itemId)) {
                    return;
                }

                if (CatalogAPI.type === 'courses') {
                    const subjectIds = getCheckedIntegerValues('input[name="courseSubjectIds[]"]:checked');

                    await CatalogAPI.request(`/api/catalog/courses/${itemId}/subjects`, {
                        method: 'PUT',
                        body: JSON.stringify({ subject_ids: subjectIds }),
                    });
                }

                if (CatalogAPI.type === 'subjects') {
                    const nameInput = document.getElementById('editSubjectNameInput');
                    const codeInput = document.getElementById('editSubjectCodeInput');
                    const unitsInput = document.getElementById('editSubjectUnitsInput');
                    const departmentInput = document.getElementById('editSubjectDepartmentInput');
                    const freeForAllInput = document.getElementById('editSubjectFreeForAllInput');

                    const subjectName = String(nameInput?.value || '').trim();
                    const subjectCode = String(codeInput?.value || '').trim().toUpperCase();
                    const units = Number.parseInt(unitsInput?.value || '', 10);
                    const isFreeForAll = Boolean(freeForAllInput?.checked);
                    const departmentId = Number.parseInt(departmentInput?.value || '', 10);
                    const courseIds = getCheckedIntegerValues('input[name="editSubjectCourseIds[]"]:checked');

                    if (!subjectName) {
                        alert('Please enter subject name.');
                        return;
                    }

                    if (!subjectCode) {
                        alert('Please enter subject code.');
                        return;
                    }

                    if (!Number.isInteger(units) || units < 1) {
                        alert('Please enter valid subject units.');
                        return;
                    }

                    if (!isFreeForAll && (!Number.isInteger(departmentId) || departmentId <= 0)) {
                        alert('Please select a department or enable Free for all departments.');
                        return;
                    }

                    await CatalogAPI.request(`/api/catalog/subjects/${itemId}/courses`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            name: subjectName,
                            subject_code: subjectCode,
                            units,
                            is_free_for_all: isFreeForAll,
                            department_id: isFreeForAll ? null : departmentId,
                            course_ids: courseIds,
                        }),
                    });
                }

                if (CatalogAPI.type === 'departments') {
                    const nameInput = document.getElementById('editDepartmentNameInput');
                    const departmentName = String(nameInput?.value || '').trim();
                    const courseIds = getCheckedIntegerValues('input[name="editDepartmentCourseIds[]"]:checked');

                    if (!departmentName) {
                        alert('Please enter department name.');
                        return;
                    }

                    await CatalogAPI.request(`/api/catalog/departments/${itemId}/courses`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            name: departmentName,
                            course_ids: courseIds,
                        }),
                    });
                }

                closeEditCatalogModal();
                await CatalogAPI.loadPage(CatalogAPI.currentPage);
            });
        }

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeAddCatalogModal();
                }
            });
        }

        if (editModal) {
            editModal.addEventListener('click', (event) => {
                if (event.target === editModal) {
                    closeEditCatalogModal();
                }
            });
        }
    } catch (error) {
        alert(error.message || 'Failed to load catalog page');
    }
});
