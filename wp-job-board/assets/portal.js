/* WP Job Board — Client Portal JS */
(function () {
    'use strict';

    var cfg     = window.WJB_PORTAL || {};
    var ajaxUrl = cfg.ajaxUrl || '';
    var nonce   = cfg.nonce   || '';

    // ── Stat counters (synced with PHP render) ────────────────────────────
    var stats = {
        active: parseInt(document.getElementById('wjbp-stat-active')?.textContent || '0', 10),
        hidden: parseInt(document.getElementById('wjbp-stat-hidden')?.textContent || '0', 10),
        total:  parseInt(document.getElementById('wjbp-stat-total')?.textContent  || '0', 10),
        month:  parseInt(document.getElementById('wjbp-stat-month')?.textContent  || '0', 10),
    };

    function updateStatEl(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function syncStats() {
        updateStatEl('wjbp-stat-active', stats.active);
        updateStatEl('wjbp-stat-hidden', stats.hidden);
        updateStatEl('wjbp-stat-total',  stats.total);
        updateStatEl('wjbp-stat-month',  stats.month);
    }

    // ── Sector / location combo fields ───────────────────────────────────
    var comboValues = {
        sector: getUniqueRowValues('sector'),
        location: getUniqueRowValues('location')
    };

    function getUniqueRowValues(key) {
        var seen = {};
        var values = [];
        document.querySelectorAll('.wjbp-row').forEach(function (row) {
            var val = (row.dataset[key] || '').trim();
            var id = val.toLowerCase();
            if (!val || seen[id]) return;
            seen[id] = true;
            values.push(val);
        });
        return values.sort(function (a, b) { return a.localeCompare(b); });
    }

    function addComboValue(key, value) {
        value = (value || '').trim();
        if (!value || !comboValues[key]) return;
        var exists = comboValues[key].some(function (item) {
            return item.toLowerCase() === value.toLowerCase();
        });
        if (!exists) {
            comboValues[key].push(value);
            comboValues[key].sort(function (a, b) { return a.localeCompare(b); });
        }
    }

    function initComboField(inputId, key) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var wrap = document.createElement('div');
        wrap.className = 'wjbp-combo';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var list = document.createElement('div');
        list.className = 'wjbp-combo-list';
        list.setAttribute('role', 'listbox');
        wrap.appendChild(list);

        var activeIndex = -1;
        var items = [];

        function close() {
            list.classList.remove('is-open');
            activeIndex = -1;
        }

        function choose(value) {
            input.value = value;
            close();
            input.focus();
        }

        function render() {
            var q = input.value.trim().toLowerCase();
            var exact = false;
            items = comboValues[key].filter(function (value) {
                if (value.toLowerCase() === q) exact = true;
                return !q || value.toLowerCase().indexOf(q) !== -1;
            }).slice(0, 8);

            list.innerHTML = '';
            items.forEach(function (value, i) {
                var option = document.createElement('button');
                option.type = 'button';
                option.className = 'wjbp-combo-option' + (i === activeIndex ? ' is-active' : '');
                option.setAttribute('role', 'option');
                option.textContent = value;
                option.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    choose(value);
                });
                list.appendChild(option);
            });

            if (q && !exact) {
                var custom = document.createElement('button');
                custom.type = 'button';
                custom.className = 'wjbp-combo-option wjbp-combo-option--custom' + (activeIndex === items.length ? ' is-active' : '');
                custom.innerHTML = 'Use <strong>“' + esc(input.value.trim()) + '”</strong>';
                custom.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    choose(input.value.trim());
                });
                list.appendChild(custom);
                items.push(input.value.trim());
            }

            list.classList.toggle('is-open', list.children.length > 0 && document.activeElement === input);
        }

        input.addEventListener('focus', render);
        input.addEventListener('input', function () { activeIndex = -1; render(); });
        input.addEventListener('blur', function () { setTimeout(close, 120); });
        input.addEventListener('keydown', function (e) {
            if (!list.classList.contains('is-open') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) render();
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                render();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                render();
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                choose(items[activeIndex]);
            } else if (e.key === 'Escape') {
                close();
            }
        });
    }

    initComboField('wjbp-f-sector', 'sector');
    initComboField('wjbp-f-location', 'location');

    // ── Toast ─────────────────────────────────────────────────────────────
    var toastEl    = document.getElementById('wjbp-toast');
    var toastTimer = null;

    function showToast(msg, type) {
        if (!toastEl) return;
        clearTimeout(toastTimer);
        toastEl.textContent = msg;
        toastEl.className   = 'wjbp-toast is-visible' + (type ? ' wjbp-toast--' + type : '');
        toastTimer = setTimeout(function () {
            toastEl.classList.remove('is-visible');
        }, 3000);
    }

    // ── AJAX helper ───────────────────────────────────────────────────────
    function post(action, data, onSuccess, onError) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce',  nonce);
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });

        fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    if (onSuccess) onSuccess(json.data);
                } else {
                    var msg = (json.data && json.data.message) || 'Something went wrong.';
                    showToast(msg, 'error');
                    if (onError) onError(msg);
                }
            })
            .catch(function () {
                showToast('Network error. Please try again.', 'error');
                if (onError) onError();
            });
    }

    // ── Modal (add / edit) ────────────────────────────────────────────────
    var overlay    = document.getElementById('wjbp-modal-overlay');
    var form       = document.getElementById('wjbp-job-form');
    var modalTitle = document.getElementById('wjbp-modal-heading');
    var saveBtn    = document.getElementById('wjbp-form-save');

    function openModal(title, rowData) {
        if (!overlay || !form) return;

        form.reset();
        document.getElementById('wjbp-job-id').value = rowData ? rowData.id : '';
        if (modalTitle) modalTitle.textContent = rowData ? 'Edit Job' : 'Add Job';
        if (saveBtn) saveBtn.textContent = rowData ? 'Save Changes' : 'Save Job';

        if (rowData) {
            setField('wjbp-f-title',       rowData.title);
            setField('wjbp-f-sector',      rowData.sector);
            setField('wjbp-f-location',    rowData.location);
            setField('wjbp-f-level',       rowData.level);
            setField('wjbp-f-type',        rowData.type);
            setField('wjbp-f-apply',       rowData.apply);
            setField('wjbp-f-description', rowData.description);
            var activeCheck = document.getElementById('wjbp-f-active');
            if (activeCheck) activeCheck.checked = (rowData.active === '1');
        }

        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        var firstInput = form.querySelector('input:not([type=hidden]), select, textarea');
        if (firstInput) setTimeout(function () { firstInput.focus(); }, 80);
    }

    function closeModal() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function setField(id, val) {
        var el = document.getElementById(id);
        if (!el) return;
        el.value = val || '';
    }

    // Open: Add btn
    document.getElementById('wjbp-add-btn')?.addEventListener('click', function () { openModal('Add Job', null); });
    document.getElementById('wjbp-add-btn-empty')?.addEventListener('click', function () { openModal('Add Job', null); });

    // Open: Edit btn (delegated)
    document.getElementById('wjbp-tbody')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.wjbp-edit-btn');
        if (!btn) return;
        var row = btn.closest('.wjbp-row');
        if (!row) return;
        openModal('Edit Job', {
            id:          row.dataset.id,
            title:       row.dataset.title,
            description: row.dataset.description,
            sector:      row.dataset.sector,
            location:    row.dataset.location,
            level:       row.dataset.level,
            type:        row.dataset.type,
            apply:       row.dataset.apply,
            active:      row.dataset.active,
        });
    });

    // Close
    document.getElementById('wjbp-modal-close')?.addEventListener('click', closeModal);
    document.getElementById('wjbp-form-cancel')?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    // ── Form submit (add / edit) ──────────────────────────────────────────
    form?.addEventListener('submit', function (e) {
        e.preventDefault();

        var id          = document.getElementById('wjbp-job-id').value;
        var title       = document.getElementById('wjbp-f-title').value.trim();
        var description = document.getElementById('wjbp-f-description').value;
        var sector      = document.getElementById('wjbp-f-sector').value.trim();
        var location    = document.getElementById('wjbp-f-location').value.trim();
        var level       = document.getElementById('wjbp-f-level').value;
        var type        = document.getElementById('wjbp-f-type').value;
        var apply       = document.getElementById('wjbp-f-apply').value.trim();
        var activeEl    = document.getElementById('wjbp-f-active');
        var active      = (activeEl && activeEl.checked) ? '1' : '0';

        if (!title) {
            showToast('Job title is required.', 'error');
            document.getElementById('wjbp-f-title').focus();
            return;
        }

        if (saveBtn) { saveBtn.classList.add('wjbp-btn--loading'); saveBtn.textContent = 'Saving…'; }

        var action = id ? 'wjb_portal_update_job' : 'wjb_portal_add_job';
        var payload = { id: id, title: title, description: description, sector: sector,
                        location: location, level: level, type: type, apply: apply, active: active };

        post(action, payload, function (data) {
            if (saveBtn) { saveBtn.classList.remove('wjbp-btn--loading'); }

            if (id) {
                // Update existing row
                var row = document.querySelector('.wjbp-row[data-id="' + id + '"]');
                if (row && data.job) {
                    var job = data.job;
                    var wasActive = row.dataset.active === '1';
                    var isNowActive = job.active === '1';

                    row.dataset.title       = job.title;
                    row.dataset.description = job.description;
                    row.dataset.sector      = job.sector;
                    row.dataset.location    = job.location;
                    row.dataset.level       = job.level;
                    row.dataset.type        = job.type;
                    row.dataset.apply       = job.apply;
                    row.dataset.active      = job.active;
                    addComboValue('sector', job.sector);
                    addComboValue('location', job.location);

                    row.querySelector('.wjbp-td-title').textContent = job.title;
                    row.querySelector('.wjbp-td-meta:nth-child(2)') && (row.querySelector('.wjbp-td-meta:nth-child(2)').textContent = job.sector || '—');
                    row.querySelector('.wjbp-td-meta:nth-child(3)') && (row.querySelector('.wjbp-td-meta:nth-child(3)').textContent = job.location || '—');

                    var badgeTd = row.querySelector('td:nth-child(4)');
                    if (badgeTd) {
                        badgeTd.innerHTML = job.type
                            ? '<span class="wjbp-badge">' + esc(job.type) + '</span>'
                            : '<span class="wjbp-muted">—</span>';
                    }

                    // Sync toggle if active changed
                    var toggle = row.querySelector('.wjbp-toggle');
                    if (toggle) {
                        toggle.classList.toggle('is-active', isNowActive);
                        toggle.dataset.id = job.id;
                    }

                    // Update stats if active status changed
                    if (wasActive !== isNowActive) {
                        if (isNowActive) { stats.active++; stats.hidden--; }
                        else             { stats.active--; stats.hidden++; }
                        syncStats();
                    }
                }
                showToast('Job updated.', 'success');
            } else {
                // New job: reload to get correct row + date
                showToast('Job added. Refreshing…', 'success');
                setTimeout(function () { window.location.reload(); }, 900);
                return;
            }

            closeModal();
        }, function () {
            if (saveBtn) { saveBtn.classList.remove('wjbp-btn--loading'); saveBtn.textContent = id ? 'Save Changes' : 'Save Job'; }
        });
    });

    // ── Toggle active ─────────────────────────────────────────────────────
    document.getElementById('wjbp-tbody')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.wjbp-toggle');
        if (!btn) return;

        var id  = btn.dataset.id;
        var row = btn.closest('.wjbp-row');

        btn.disabled = true;

        post('wjb_portal_toggle_job', { id: id }, function (data) {
            btn.disabled = false;
            var isActive = data.active === '1';
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-label', isActive ? 'Active — click to hide' : 'Hidden — click to activate');
            btn.setAttribute('title', isActive ? 'Active' : 'Hidden');

            if (row) row.dataset.active = data.active;

            if (isActive) { stats.active++; stats.hidden--; }
            else          { stats.active--; stats.hidden++; }
            syncStats();

            showToast(isActive ? 'Job set to active.' : 'Job hidden.', 'success');
        }, function () {
            btn.disabled = false;
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────
    var confirmOverlay = document.getElementById('wjbp-confirm-overlay');
    var confirmOk      = document.getElementById('wjbp-confirm-ok');
    var confirmCancel  = document.getElementById('wjbp-confirm-cancel');
    var pendingDeleteId = null;

    document.getElementById('wjbp-tbody')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.wjbp-delete-btn');
        if (!btn) return;
        pendingDeleteId = btn.dataset.id;
        openConfirm();
    });

    function openConfirm() {
        if (!confirmOverlay) return;
        confirmOverlay.classList.add('is-open');
        confirmOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        confirmOk?.focus();
    }

    function closeConfirm() {
        if (!confirmOverlay) return;
        confirmOverlay.classList.remove('is-open');
        confirmOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        pendingDeleteId = null;
    }

    confirmCancel?.addEventListener('click', closeConfirm);
    confirmOverlay?.addEventListener('click', function (e) {
        if (e.target === confirmOverlay) closeConfirm();
    });

    confirmOk?.addEventListener('click', function () {
        if (!pendingDeleteId) return;
        var id = pendingDeleteId;
        confirmOk.disabled = true;

        post('wjb_portal_delete_job', { id: id }, function () {
            confirmOk.disabled = false;
            closeConfirm();

            var row = document.querySelector('.wjbp-row[data-id="' + id + '"]');
            if (row) {
                var wasActive = row.dataset.active === '1';
                if (wasActive) stats.active--; else stats.hidden--;
                stats.total--;
                syncStats();

                row.classList.add('wjbp-row-removing');
                setTimeout(function () {
                    row.remove();
                    maybeShowEmpty();
                }, 280);
            }

            showToast('Job deleted.', 'success');
        }, function () {
            confirmOk.disabled = false;
        });
    });

    function maybeShowEmpty() {
        var tbody = document.getElementById('wjbp-tbody');
        var table = document.getElementById('wjbp-table');
        var empty = document.getElementById('wjbp-empty-state');
        if (!tbody) return;

        if (tbody.querySelectorAll('.wjbp-row').length === 0) {
            if (table) table.classList.add('wjbp-hidden');
            if (empty) empty.style.display = '';
        }
    }

    // ── Keyboard: Escape closes any open overlay ──────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (confirmOverlay?.classList.contains('is-open')) { closeConfirm(); return; }
        if (overlay?.classList.contains('is-open'))       { closeModal(); }
    });

    // ── Safe HTML escaping for dynamic content ────────────────────────────
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
