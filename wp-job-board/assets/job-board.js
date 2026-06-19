/* WP Job Board — frontend JS */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var search         = document.getElementById('wjb-search');
        var grid           = document.getElementById('wjb-grid');
        var empty          = document.getElementById('wjb-empty');
        var countEl        = document.getElementById('wjb-count');
        var resetBtn       = document.getElementById('wjb-reset');
        var overlay        = document.getElementById('wjb-modal-overlay');
        var closeBtn       = document.getElementById('wjb-modal-close');

        var pillsWrap      = document.getElementById('wjb-sector-pills');
        var filterLocation = document.getElementById('wjb-filter-location');
        var filterLevel    = document.getElementById('wjb-filter-level');
        var filterType     = document.getElementById('wjb-filter-type');

        if (!grid) return;

        var pills = pillsWrap ? Array.from(pillsWrap.querySelectorAll('.wjb-sector-pill')) : [];
        var cards = Array.from(grid.querySelectorAll('.wjb-card'));
        var total = cards.length;

        // Currently selected sector (from the active pill, '' = all)
        function selectedSector() {
            var active = pillsWrap ? pillsWrap.querySelector('.wjb-sector-pill.is-active') : null;
            return active ? (active.dataset.sector || '') : '';
        }

        // ── Filter logic ──────────────────────────────────────────────────
        function hasActiveFilters() {
            var q = search         ? search.value.trim()         : '';
            var s = selectedSector();
            var l = filterLocation ? filterLocation.value        : '';
            var v = filterLevel    ? filterLevel.value           : '';
            var t = filterType     ? filterType.value            : '';
            return !!(q || s || l || v || t);
        }

        function applyFilters() {
            var query    = search         ? search.value.toLowerCase().trim()         : '';
            var sector   = selectedSector().toLowerCase();
            var location = filterLocation ? filterLocation.value.toLowerCase()        : '';
            var level    = filterLevel    ? filterLevel.value.toLowerCase()           : '';
            var type     = filterType     ? filterType.value.toLowerCase()            : '';

            var visible = 0;

            cards.forEach(function (card) {
                var title   = (card.dataset.title    || '').toLowerCase();
                var cSector = (card.dataset.sector   || '').toLowerCase();
                var cLoc    = (card.dataset.location || '').toLowerCase();
                var cLevel  = (card.dataset.level    || '').toLowerCase();
                var cType   = (card.dataset.type     || '').toLowerCase();
                var desc    = (card.dataset.desc     || '').toLowerCase();

                var matchSearch   = !query    || title.includes(query)   || desc.includes(query);
                var matchSector   = !sector   || cSector   === sector;
                var matchLocation = !location || cLoc      === location;
                var matchLevel    = !level    || cLevel    === level;
                var matchType     = !type     || cType     === type;

                var show = matchSearch && matchSector && matchLocation && matchLevel && matchType;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (empty) {
                empty.style.display = visible === 0 ? 'block' : 'none';
            }

            if (countEl) {
                if (hasActiveFilters()) {
                    countEl.textContent = 'Showing ' + visible + ' of ' + total + (total === 1 ? ' role' : ' roles');
                } else {
                    countEl.textContent = total + (total === 1 ? ' open role' : ' open roles');
                }
            }

            if (resetBtn) {
                resetBtn.style.display = hasActiveFilters() ? 'inline-block' : 'none';
            }
        }

        // Sector pills — single-select toggle (click active pill to clear)
        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                var wasActive = pill.classList.contains('is-active');
                pills.forEach(function (p) {
                    p.classList.remove('is-active');
                    p.setAttribute('aria-pressed', 'false');
                });
                if (!wasActive) {
                    pill.classList.add('is-active');
                    pill.setAttribute('aria-pressed', 'true');
                }
                applyFilters();
            });
        });

        // Reset all filters
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (search)         search.value         = '';
                if (filterLocation) filterLocation.value = '';
                if (filterLevel)    filterLevel.value    = '';
                if (filterType)     filterType.value     = '';
                pills.forEach(function (p) {
                    p.classList.remove('is-active');
                    p.setAttribute('aria-pressed', 'false');
                });
                applyFilters();
            });
        }

        // Debounced search (200 ms)
        var searchTimer;
        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(applyFilters, 200);
            });
        }

        if (filterLocation) filterLocation.addEventListener('change', applyFilters);
        if (filterLevel)    filterLevel.addEventListener('change',    applyFilters);
        if (filterType)     filterType.addEventListener('change',     applyFilters);


        // ── Modal logic ───────────────────────────────────────────────────
        function openModal(btn) {
            var title  = btn.dataset.title  || '';
            var tags   = btn.dataset.tags   || '';
            var desc   = btn.dataset.desc   || '';
            var apply  = btn.dataset.apply  || '';
            var type   = btn.dataset.type   || '';
            var posted = btn.dataset.posted || '';

            document.getElementById('wjb-modal-tags').textContent  = tags;
            document.getElementById('wjb-modal-title').textContent = title;
            document.getElementById('wjb-modal-body').innerHTML    = desc || '<p>No description provided.</p>';

            // Meta chips
            var metaEl = document.getElementById('wjb-modal-meta');
            metaEl.innerHTML = '';
            [type, posted ? 'Posted ' + posted : ''].forEach(function (text) {
                if (!text) return;
                var span = document.createElement('span');
                span.className   = 'wjb-modal-meta-chip';
                span.textContent = text;
                metaEl.appendChild(span);
            });

            // Footer apply button
            var footer = document.getElementById('wjb-modal-footer');
            footer.innerHTML = '';
            if (apply) {
                var a = document.createElement('a');
                a.href        = apply;
                a.target      = '_blank';
                a.rel         = 'noopener noreferrer';
                a.className   = 'wjb-btn wjb-btn-solid';
                a.textContent = 'Apply for this role';
                footer.appendChild(a);
            }

            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            if (closeBtn) closeBtn.focus();
        }

        function closeModal() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        // Open via card buttons (event delegation)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.wjb-open-modal');
            if (btn) {
                e.preventDefault();
                openModal(btn);
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('is-open')) {
                closeModal();
            }
        });

    });
})();
