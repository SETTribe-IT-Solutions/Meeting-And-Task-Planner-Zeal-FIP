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

    // Use IntersectionObserver for scroll-triggered counters
    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounters();
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.counter-value').forEach(function (el) {
            counterObserver.observe(el);
        });
    } else {
        animateCounters();
    }

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
        if (allRows.length <= 5) return; // Don't paginate tiny tables

        var defaultPerPage = parseInt(wrapper.getAttribute('data-per-page')) || 10;
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
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
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

});
