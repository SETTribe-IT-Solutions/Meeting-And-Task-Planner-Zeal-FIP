/* ═══════════════════════════════════════════════════════════
   APP.JS – Latur District Meeting & Task Planner
   Client-side Pagination, Table Filters, Animations
   ═══════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function () {

    // ═══════════════════════════════════════
    // 1. ANIMATED COUNTERS
    // ═══════════════════════════════════════
    function animateCounters() {
        document.querySelectorAll('.counter-value').forEach(function (el) {
            if (el.dataset.animated === 'true') return;
            var target = parseInt(el.getAttribute('data-target')) || 0;
            var duration = 1200;
            var startTime = null;
            var startVal = 0;

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
                el.textContent = Math.floor(eased * (target - startVal) + startVal);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target;
                    el.dataset.animated = 'true';
                }
            }
            requestAnimationFrame(step);
        });
    }

    // Use IntersectionObserver for scroll-triggered counters.
    // Threshold 0.1 (not 0.3) because counter elements sit inside animate-on-scroll
    // wrappers that start at opacity:0 — some browsers skip intersection checks on
    // children of invisible ancestors with a higher threshold.
    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounters();
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.counter-value').forEach(function (el) {
            counterObserver.observe(el);
        });
    } else {
        animateCounters();
    }

    // Fallback: fire after the fade-in animation (0.6s) completes so any counter
    // whose observer was skipped due to opacity:0 ancestor still animates.
    setTimeout(animateCounters, 700);

    // ═══════════════════════════════════════
    // 2. SCROLL-TRIGGERED FADE-IN ANIMATIONS
    // ═══════════════════════════════════════
    if ('IntersectionObserver' in window) {
        var animObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    animObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

        document.querySelectorAll('.animate-on-scroll').forEach(function (el) {
            el.style.opacity = '0';
            animObserver.observe(el);
        });
    }

    // ═══════════════════════════════════════
    // 3. TABLE PAGINATION
    // ═══════════════════════════════════════
    document.querySelectorAll('[data-paginate]').forEach(function (wrapper) {
        var table = wrapper.querySelector('table');
        if (!table) return;

        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        var allRows = Array.from(tbody.querySelectorAll('tr'));
        var defaultPerPage = parseInt(wrapper.getAttribute('data-per-page')) || 10;
        if (allRows.length < defaultPerPage) return; // Don't paginate if fewer rows than one page

        var currentPage = 1;
        var perPage = defaultPerPage;
        var filteredRows = allRows.slice();

        // Create pagination container
        var paginationDiv = document.createElement('div');
        paginationDiv.className = 'pagination-wrapper';
        paginationDiv.innerHTML =
            '<div class="d-flex align-items-center gap-3 flex-wrap">' +
                '<div class="pagination-info"></div>' +
                '<div class="rows-per-page">' +
                    '<span>Rows:</span>' +
                    '<select>' +
                        '<option value="10"' + (defaultPerPage === 10 ? ' selected' : '') + '>10</option>' +
                        '<option value="25"' + (defaultPerPage === 25 ? ' selected' : '') + '>25</option>' +
                        '<option value="50"' + (defaultPerPage === 50 ? ' selected' : '') + '>50</option>' +
                        '<option value="100"' + (defaultPerPage === 100 ? ' selected' : '') + '>100</option>' +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="pagination-controls"></div>';

        // Insert after table's parent
        var tableParent = table.closest('.table-responsive') || table.parentNode;
        tableParent.parentNode.insertBefore(paginationDiv, tableParent.nextSibling);

        var infoEl = paginationDiv.querySelector('.pagination-info');
        var controlsEl = paginationDiv.querySelector('.pagination-controls');
        var perPageSelect = paginationDiv.querySelector('.rows-per-page select');

        perPageSelect.addEventListener('change', function () {
            perPage = parseInt(this.value);
            currentPage = 1;
            render();
        });

        function render() {
            var totalRows = filteredRows.length;
            var totalPages = Math.ceil(totalRows / perPage);
            if (currentPage > totalPages) currentPage = totalPages || 1;

            var start = (currentPage - 1) * perPage;
            var end = Math.min(start + perPage, totalRows);

            // Hide all, show only current page
            allRows.forEach(function (r) { r.style.display = 'none'; });
            for (var i = start; i < end; i++) {
                filteredRows[i].style.display = '';
            }

            // Info text
            if (totalRows === 0) {
                infoEl.textContent = 'No records found';
            } else {
                infoEl.textContent = 'Showing ' + (start + 1) + '–' + end + ' of ' + totalRows + ' records';
            }

            // Page buttons
            var html = '';
            html += '<button class="page-btn" data-page="prev" ' + (currentPage === 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i></button>';

            // Calculate visible page range
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, startPage + 4);
            if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

            if (startPage > 1) {
                html += '<button class="page-btn" data-page="1">1</button>';
                if (startPage > 2) html += '<span class="px-1 text-muted">…</span>';
            }

            for (var p = startPage; p <= endPage; p++) {
                html += '<button class="page-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += '<span class="px-1 text-muted">…</span>';
                html += '<button class="page-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
            }

            html += '<button class="page-btn" data-page="next" ' + (currentPage === totalPages ? 'disabled' : '') + '><i class="fas fa-chevron-right"></i></button>';

            controlsEl.innerHTML = html;

            // Bind click events
            controlsEl.querySelectorAll('.page-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var page = this.getAttribute('data-page');
                    if (page === 'prev') currentPage = Math.max(1, currentPage - 1);
                    else if (page === 'next') currentPage = Math.min(totalPages, currentPage + 1);
                    else currentPage = parseInt(page);
                    render();
                });
            });
        }

        // Store render and filter for table search
        wrapper._paginateRender = render;
        wrapper._paginateSetFiltered = function (rows) {
            filteredRows = rows;
            currentPage = 1;
            render();
        };
        wrapper._paginateAllRows = allRows;

        render();
    });

    // ═══════════════════════════════════════
    // 4. TABLE SEARCH / FILTER
    // ═══════════════════════════════════════
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        var targetId = input.getAttribute('data-table-search');
        var wrapper = document.getElementById(targetId);
        if (!wrapper) return;

        var countEl = input.closest('.table-filter-bar') ? input.closest('.table-filter-bar').querySelector('.table-result-count') : null;

        input.addEventListener('input', function () {
            var query = this.value.toLowerCase().trim();
            var allRows = wrapper._paginateAllRows || Array.from(wrapper.querySelectorAll('tbody tr'));

            if (!query) {
                // Reset filter
                if (wrapper._paginateSetFiltered) {
                    wrapper._paginateSetFiltered(allRows);
                } else {
                    allRows.forEach(function (r) { r.style.display = ''; });
                }
                if (countEl) countEl.textContent = allRows.length + ' records';
                return;
            }

            var matched = allRows.filter(function (row) {
                var text = row.textContent.toLowerCase();
                return text.indexOf(query) !== -1;
            });

            if (wrapper._paginateSetFiltered) {
                wrapper._paginateSetFiltered(matched);
            } else {
                allRows.forEach(function (r) { r.style.display = 'none'; });
                matched.forEach(function (r) { r.style.display = ''; });
            }

            if (countEl) countEl.textContent = matched.length + ' of ' + allRows.length + ' records';
        });
    });

    // ═══════════════════════════════════════
    // 5. ALERT AUTO-DISMISS
    // ═══════════════════════════════════════
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function () {
                if (alert.parentNode) alert.parentNode.removeChild(alert);
            }, 500);
        }, 5000);
    });

    // ═══════════════════════════════════════
    // 6. PROGRESS BAR ANIMATION ON SCROLL
    // ═══════════════════════════════════════
    if ('IntersectionObserver' in window) {
        var progressObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('progress-animated');
                    progressObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.progress').forEach(function (el) {
            progressObserver.observe(el);
        });
    }

    // ═══════════════════════════════════════
    // 7. SIDEBAR MOBILE TOGGLE
    // ═══════════════════════════════════════
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    var sidebarBackdrop = document.getElementById('sidebarBackdrop');
    if (sidebarToggle && sidebar) {
        var sidebarIcon = sidebarToggle.querySelector('i');

        function setSidebarOpen(isOpen) {
            sidebar.classList.toggle('sidebar-open', isOpen);
            if (sidebarBackdrop) sidebarBackdrop.classList.toggle('show', isOpen);
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (sidebarIcon) {
                sidebarIcon.classList.toggle('fa-bars', !isOpen);
                sidebarIcon.classList.toggle('fa-times', isOpen);
            }
        }

        sidebarToggle.addEventListener('click', function () {
            setSidebarOpen(!sidebar.classList.contains('sidebar-open'));
        });

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function () {
                setSidebarOpen(false);
            });
        }

        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 750px)').matches) {
                    setSidebarOpen(false);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
                setSidebarOpen(false);
                sidebarToggle.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 750px)').matches) {
                setSidebarOpen(false);
            }
        });
    }

    // ═══════════════════════════════════════
    // 8. SMOOTH SCROLL FOR ANCHOR LINKS
    // ═══════════════════════════════════════
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ═══════════════════════════════════════
    // 9. TOOLTIP INIT (Bootstrap)
    // ═══════════════════════════════════════
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    var pastDateMessage = 'Past dates are not allowed. Please select today or a future date.';

    function getTodayDateString() {
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function validateDueDateInput(input) {
        if (!input) return true;
        var minimumDate = getTodayDateString();
        input.min = minimumDate;
        if (input.value && input.value < minimumDate) {
            input.setCustomValidity(input.getAttribute('data-past-date-message') || pastDateMessage);
            return false;
        }

        input.setCustomValidity('');
        return true;
    }

    function attachDueDateValidation() {
        var today = getTodayDateString();
        document.querySelectorAll('input[type="date"][name="due_date"]').forEach(function (input) {
            input.min = today;
            if (!input.getAttribute('data-past-date-message')) {
                input.setAttribute('data-past-date-message', pastDateMessage);
            }

            if (!input.dataset.dueDateValidationAttached) {
                input.addEventListener('input', function () {
                    validateDueDateInput(input);
                });

                input.addEventListener('change', function () {
                    if (!validateDueDateInput(input)) {
                        input.reportValidity();
                    }
                });

                input.dataset.dueDateValidationAttached = 'true';
            }

            var form = input.form;
            if (form && !form.dataset.dueDateValidationAttached) {
                form.addEventListener('submit', function (event) {
                    var invalidDueDate = null;
                    form.querySelectorAll('input[type="date"][name="due_date"]').forEach(function (dateInput) {
                        if (!invalidDueDate && !validateDueDateInput(dateInput)) {
                            invalidDueDate = dateInput;
                        }
                    });

                    if (invalidDueDate) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        invalidDueDate.reportValidity();
                    }
                });

                form.dataset.dueDateValidationAttached = 'true';
            }

            validateDueDateInput(input);
        });
    }

    attachDueDateValidation();

    // ═══════════════════════════════════════
    // 10. OPEN 'ASSIGN TASK' MODAL FROM MEETINGS LIST
    // Prefill meeting, assignee (organizer), and due date
    // ═══════════════════════════════════════
    document.querySelectorAll('.open-add-task-modal').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var meetingId = this.getAttribute('data-meeting-id');
            var meetingDate = this.getAttribute('data-meeting-date');
            var organizerId = this.getAttribute('data-organizer-id');
            var meetingTitle = this.getAttribute('data-meeting-title');

            var modalEl = document.getElementById('addTaskModal');
            if (!modalEl) return;

            // Set meeting select
            var meetingSelect = modalEl.querySelector('#modal_meeting_id');
            if (meetingSelect) {
                meetingSelect.value = meetingId || '';
            }

            // Set due date to meeting date when possible, without allowing past dates.
            var dueInput = modalEl.querySelector('#modal_due_date');
            if (dueInput) {
                var todayDate = getTodayDateString();
                dueInput.min = todayDate;
                if (meetingDate) {
                    dueInput.value = meetingDate < todayDate ? todayDate : meetingDate;
                }
                validateDueDateInput(dueInput);
            }

            // Prefer to preselect organizer as assignee if present in list
            var assigneeSelect = modalEl.querySelector('#modal_assigned_to');
            if (assigneeSelect && organizerId) {
                var opt = assigneeSelect.querySelector('option[value="' + organizerId + '"]');
                if (opt) assigneeSelect.value = organizerId;
            }

            // Set department and populate assignees for that department
            var meetingDept = this.getAttribute('data-meeting-department');
            var deptSelect = modalEl.querySelector('#modal_department_select');
            if (deptSelect && meetingDept) {
                deptSelect.value = meetingDept;
                var modalAssignee = modalEl.querySelector('#modal_assigned_to');
                populateUsersForDepartment(meetingDept, modalAssignee, organizerId ? [organizerId] : []);
            }

            // Pre-fill title template
            var titleInput = modalEl.querySelector('#modal_task_title');
            if (titleInput) {
                titleInput.value = meetingTitle ? ('Follow-up: ' + meetingTitle) : '';
            }

            var modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Focus the title input when modal is fully shown
            modalEl.addEventListener('shown.bs.modal', function () {
                if (titleInput) {
                    setTimeout(function () { titleInput.focus(); }, 50);
                }
            }, { once: true });
        });
    });

    // Handle modal form submission via AJAX
    var addTaskModalEl = document.getElementById('addTaskModal');
    if (addTaskModalEl) {
        var modalForm = addTaskModalEl.querySelector('form');
        modalForm && modalForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var modalDueInput = modalForm.querySelector('input[type="date"][name="due_date"]');
            validateDueDateInput(modalDueInput);
            if (!modalForm.checkValidity()) {
                modalForm.reportValidity();
                return;
            }

            var action = modalForm.getAttribute('action') || window.location.href;
            var formData = new FormData(modalForm);
            formData.append('ajax', '1');

            fetch(action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    // Close modal
                    var bsModal = bootstrap.Modal.getInstance(addTaskModalEl);
                    if (bsModal) bsModal.hide();

                    // Show success alert
                    var alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show rounded-3 mt-3';
                    alert.role = 'alert';
                    alert.innerHTML = (data.message || 'Task created.') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    var container = document.querySelector('.main-content') || document.body;
                    container.insertBefore(alert, container.firstChild);

                    // If tasks table exists on this page, refresh it via AJAX
                    var tasksWrapper = document.getElementById('tasksTableWrapper');
                    if (tasksWrapper) {
                        // Fetch updated tasks JSON
                        fetch('../tasks/index.php?ajax=1', { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (tasks) {
                            // Rebuild tbody
                            var tbody = tasksWrapper.querySelector('tbody');
                            if (!tbody) return;
                            tbody.innerHTML = '';
                            tasks.forEach(function (task) {
                                var tr = document.createElement('tr');
                                var dueDate = new Date(task.due_date);
                                var dueText = isNaN(dueDate.getTime()) ? '' : dueDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                                    tr.innerHTML = '\\
                                        <td>#' + escapeHtml(task.id) + '</td>\\n\\
                                        <td>\\n\\
                                            <div class="fw-bold text-dark">' + escapeHtml(task.title) + '</div>\\n\\
                                            <small class="text-muted d-block">' + escapeHtml(task.notes || '') + '</small>\\n\\
                                        </td>\\n\\
                                        <td><span class="badge bg-light text-dark border">' + escapeHtml(task.meeting_department || '') + '</span></td>\\n\\
                                        <td>\\n\\
                                            <div class="fw-semibold">' + escapeHtml(task.assignees || '') + '</div>\\n\\
                                        </td>\\n\\
                                        <td>\\n\\
                                            <div class="fw-semibold">' + escapeHtml(task.organizer_name || '') + '</div>\\n\\
                                        </td>\\n\\
                                        <td>\\n\\
                                            <a href="../meetings/view.php?id=' + encodeURIComponent(task.meeting_id) + '" class="text-decoration-none fw-semibold">' + escapeHtml(task.meeting_title || '') + '</a>\\n\\
                                        </td>\\n\\
                                        <td>\\n\\
                                            <span>' + escapeHtml(dueText) + '</span>\\n\\
                                        </td>\\n\\
                                        <td>\\n\\
                                            <span class="badge ' + (task.priority === 'High' ? 'badge-priority-high' : (task.priority === 'Medium' ? 'badge-priority-medium' : 'badge-priority-low')) + '">' + escapeHtml(task.priority || '') + '</span>\\n\\
                                        </td>\\n\\
                                        <td>\\n\\
                                            <span class="badge">' + escapeHtml(task.status || '') + '</span>\\n\\
                                        </td>\\n\\
                                        <td class="text-end">\\n\\
                                            <a href="create.php?id=' + encodeURIComponent(task.id) + '" class="btn btn-sm btn-outline-secondary me-1">Edit</a>\\n\\
                                            <a href="view.php?id=' + encodeURIComponent(task.id) + '" class="btn btn-sm btn-outline-primary me-1">View</a>\\n\\
                                            <a href="../../controllers/DeleteTaskController.php?id=' + encodeURIComponent(task.id) + '" class="btn btn-sm btn-outline-danger">Delete</a>\\n\\
                                        </td>';
                                tbody.appendChild(tr);
                            });
                        }).catch(function () { /* ignore refresh errors */ });
                    }

                } else {
                    var msg = (data && data.message) ? data.message : 'Unable to create task.';
                    var alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show rounded-3 mt-3';
                    alert.role = 'alert';
                    alert.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    var container = document.querySelector('.main-content') || document.body;
                    container.insertBefore(alert, container.firstChild);
                }
            }).catch(function () {
                var alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show rounded-3 mt-3';
                alert.role = 'alert';
                alert.innerHTML = 'Network error while creating task.' + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                var container = document.querySelector('.main-content') || document.body;
                container.insertBefore(alert, container.firstChild);
            });
        });
    }

    // Simple HTML escape helper
    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"'`]/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;","`":"&#96;"})[s];
        });
    }

    // Helper to determine controller path relative to current page
    function getUsersByDeptPath() {
        var path = window.location.pathname;
        if (path.indexOf('/modules/meetings/') !== -1) return '../../controllers/GetUsersByDepartment.php';
        if (path.indexOf('/modules/tasks/') !== -1) return '../../controllers/GetUsersByDepartment.php';
        return '../../controllers/GetUsersByDepartment.php';
    }

    function populateUsersForDepartment(department, selectEl, preselected) {
        if (!department || !selectEl) return;
        selectEl.innerHTML = '';
        var url = getUsersByDeptPath() + '?department=' + encodeURIComponent(department);
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (users) {
                users.forEach(function (u) {
                    var opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = u.name + ' (' + u.email + ')';
                    selectEl.appendChild(opt);
                });
                if (preselected && preselected.length) {
                    try { selectEl.value = preselected; } catch (e) { /* ignore */ }
                }
            }).catch(function () { /* ignore fetch errors */ });
    }

    // Wire up modal department select (used in meetings list quick-add task modal)
    var modalDeptEl = document.getElementById('modal_department_select');
    if (modalDeptEl) {
        var modalAssigneeEl = document.getElementById('modal_assigned_to');
        modalDeptEl.addEventListener('change', function () {
            populateUsersForDepartment(this.value, modalAssigneeEl, []);
        });
    }

});
