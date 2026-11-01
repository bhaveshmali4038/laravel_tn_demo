import $ from 'jquery';
import 'datatables.net-bs5';
import * as bootstrap from 'bootstrap';

// The npm modules share one jQuery instance. Vite defers this entry until the DOM is parsed.
window.$ = window.jQuery = $;
window.bootstrap = bootstrap;

(function () {
    'use strict';

    var imageUrls = new WeakMap();
    var slotSequence = 0;
    var tableInstances = [];

    function isAvailable(group) {
        var dayToggle = group.querySelector('[data-day-toggle]');
        var availabilitySelect = group.querySelector('[data-availability-select]');
        return dayToggle ? dayToggle.checked : !availabilitySelect || availabilitySelect.value === '1';
    }

    function updateGroup(group) {
        var available = isAvailable(group);
        var body = group.querySelector('[data-slot-body]');
        var note = group.querySelector('[data-closed-note]');
        var openNote = group.querySelector('[data-open-note]');
        var caption = group.querySelector('[data-day-caption]');
        group.classList.toggle('is-available', available);
        if (body) {
            body.hidden = !available;
            body.querySelectorAll('input').forEach(function (input) {
                input.disabled = !available;
                input.required = available;
                if (!available) input.setCustomValidity('');
            });
        }
        if (note) note.hidden = available;
        if (openNote) openNote.hidden = !available;
        if (caption) caption.textContent = available ? 'Available' : 'Closed';
        updateRemoveButtons(group);
    }

    function updateRemoveButtons(group) {
        var rows = group.querySelectorAll('[data-time-slot]');
        rows.forEach(function (row) {
            var button = row.querySelector('[data-remove-slot]');
            if (button) {
                button.disabled = rows.length <= 1;
                button.title = rows.length <= 1 ? 'An available day needs at least one time slot' : 'Remove time slot';
            }
        });
    }

    function addSlot(group) {
        var container = group.querySelector('[data-slots-container]');
        if (!container) return;
        var prefix = container.dataset.slotPrefix;
        var existing = container.querySelectorAll('[data-time-slot]');
        var nextIndex = 0;
        existing.forEach(function (row) {
            var input = row.querySelector('[data-start-time]');
            var match = input.name.match(/\[(\d+)\]\[start_time\]$/);
            if (match) nextIndex = Math.max(nextIndex, Number(match[1]) + 1);
        });
        var row = document.createElement('div');
        row.className = 'time-slot';
        row.dataset.timeSlot = '';
        ['start_time', 'end_time'].forEach(function (field, index) {
            if (index === 1) {
                var separator = document.createElement('span');
                separator.className = 'time-separator';
                separator.setAttribute('aria-hidden', 'true');
                separator.textContent = '–';
                row.appendChild(separator);
            }
            var wrapper = document.createElement('div');
            wrapper.className = 'time-field';
            var input = document.createElement('input');
            input.type = 'time';
            input.className = 'form-control';
            input.name = prefix + '[' + nextIndex + '][' + field + ']';
            input.id = 'dynamic-slot-' + (++slotSequence);
            input.required = true;
            input.setAttribute(index === 0 ? 'data-start-time' : 'data-end-time', '');
            var label = document.createElement('label');
            label.className = 'visually-hidden';
            label.htmlFor = input.id;
            label.textContent = index === 0 ? 'Opening time' : 'Closing time';
            var feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = index === 0 ? 'Enter a valid opening time.' : 'Closing time must follow opening time without overlapping another slot.';
            wrapper.append(label, input, feedback);
            row.appendChild(wrapper);
        });
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-slot-remove';
        remove.dataset.removeSlot = '';
        remove.setAttribute('aria-label', 'Remove time slot');
        remove.innerHTML = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 6h12M8 3h4l1 3H7l1-3ZM6 6l1 11h6l1-11M9 9v5M11 9v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        row.appendChild(remove);
        container.appendChild(row);
        updateGroup(group);
        row.querySelector('input').focus();
    }

    function validateTimeGroup(group) {
        var rows = Array.from(group.querySelectorAll('[data-time-slot]'));
        rows.forEach(function (row) {
            row.querySelectorAll('input').forEach(function (input) { input.setCustomValidity(''); });
        });
        if (!isAvailable(group)) return;
        var validSlots = [];
        rows.forEach(function (row) {
            var start = row.querySelector('[data-start-time]');
            var end = row.querySelector('[data-end-time]');
            if (!start.value || !end.value) return;
            if (end.value <= start.value) {
                end.setCustomValidity('The closing time must be later than the opening time on the same day.');
                return;
            }
            validSlots.push({ start: start.value, end: end.value, input: end });
        });
        validSlots.sort(function (a, b) { return a.start.localeCompare(b.start); });
        // Touching intervals are allowed: 09:00–12:00 and 12:00–14:00 do not overlap.
        validSlots.forEach(function (slot, index) {
            for (var previousIndex = 0; previousIndex < index; previousIndex++) {
                if (slot.start < validSlots[previousIndex].end) {
                    slot.input.setCustomValidity('Time slots cannot repeat or overlap.');
                    break;
                }
            }
        });
    }

    function validateDates(form) {
        var dates = form.querySelectorAll('[data-override-date]');
        var seen = new Set(JSON.parse(form.dataset.existingDates || '[]'));
        dates.forEach(function (input) {
            input.setCustomValidity('');
            if (input.value && seen.has(input.value)) input.setCustomValidity('Use each special date only once.');
            if (input.value) seen.add(input.value);
        });
    }

    function validateImages(input) {
        input.setCustomValidity('');
        var files = Array.from(input.files || []);
        var maximum = Number(input.dataset.maxFiles || 1);
        if (files.length > maximum) {
            input.setCustomValidity('Select no more than ' + maximum + ' images.');
            return;
        }
        var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        files.forEach(function (file) {
            if (!/\.(jpe?g|png|webp)$/i.test(file.name) || (file.type && !allowedTypes.includes(file.type))) {
                input.setCustomValidity('Only JPG, PNG and WebP images are allowed.');
            } else if (file.size > 4 * 1024 * 1024) {
                input.setCustomValidity('Each image must be 4 MB or smaller.');
            }
        });
    }

    function previewImages(input) {
        var target = document.getElementById(input.dataset.previewTarget);
        if (!target) return;
        (imageUrls.get(input) || []).forEach(function (url) { URL.revokeObjectURL(url); });
        imageUrls.set(input, []);
        target.replaceChildren();
        if (!input.validity.valid) return;
        Array.from(input.files || []).forEach(function (file) {
            var url = URL.createObjectURL(file);
            imageUrls.get(input).push(url);
            var figure = document.createElement('figure');
            var image = document.createElement('img');
            image.src = url;
            image.alt = 'Preview of ' + file.name;
            var caption = document.createElement('figcaption');
            caption.textContent = file.name;
            figure.append(image, caption);
            target.appendChild(figure);
        });
    }

    function validatePhone(input) {
        var value = input.value.trim();
        var digits = value.replace(/\D/g, '');
        input.setCustomValidity(value && (!/^\+?[0-9 ()\-.]+$/.test(value) || digits.length < 7 || digits.length > 15) ? 'Enter a phone number containing 7–15 digits with an optional leading +.' : '');
    }

    document.querySelectorAll('[data-time-group]').forEach(updateGroup);
    document.querySelectorAll('.needs-app-validation').forEach(function (form) {
        form.noValidate = true;
        form.addEventListener('submit', function (event) {
            form.querySelectorAll('[data-time-group]').forEach(validateTimeGroup);
            if (form.matches('[data-time-group]')) validateTimeGroup(form);
            validateDates(form);
            form.querySelectorAll('[data-image-input]').forEach(validateImages);
            form.querySelectorAll('[data-phone-input]').forEach(validatePhone);
            form.querySelectorAll('input[name="name"]').forEach(function (input) {
                input.setCustomValidity(input.value.trim() ? '' : 'Enter a name.');
            });
            form.classList.add('was-validated');
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                var invalid = form.querySelector(':invalid');
                if (invalid) {
                    invalid.focus();
                    invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                    button.classList.add('submit-busy');
                    button.textContent = 'Saving…';
                });
            }
        });
    });

    document.addEventListener('change', function (event) {
        var input = event.target;
        if (input.matches('[data-day-toggle], [data-availability-select]')) updateGroup(input.closest('[data-time-group]'));
        if (input.matches('[data-image-input]')) {
            validateImages(input);
            previewImages(input);
            input.classList.toggle('is-invalid', !input.validity.valid);
        }
        if (input.matches('[data-override-date]')) validateDates(input.form);
    });
    document.addEventListener('input', function (event) {
        var input = event.target;
        if (input.matches('input, select')) input.classList.remove('is-invalid');
        if (input.matches('[data-start-time], [data-end-time]')) validateTimeGroup(input.closest('[data-time-group]'));
        if (input.matches('[data-phone-input]')) validatePhone(input);
        if (input.matches('input[name="name"]')) input.setCustomValidity(input.value.trim() ? '' : 'Enter a name.');
    });

    document.addEventListener('click', function (event) {
        var addSlotButton = event.target.closest('[data-add-slot]');
        if (addSlotButton) addSlot(addSlotButton.closest('[data-time-group]'));
        var removeSlotButton = event.target.closest('[data-remove-slot]');
        if (removeSlotButton) {
            var group = removeSlotButton.closest('[data-time-group]');
            if (group.querySelectorAll('[data-time-slot]').length > 1) {
                removeSlotButton.closest('[data-time-slot]').remove();
                updateRemoveButtons(group);
                validateTimeGroup(group);
            }
        }
        var removeDateButton = event.target.closest('[data-remove-override]');
        if (removeDateButton) {
            var parentForm = removeDateButton.closest('form');
            removeDateButton.closest('[data-override-card]').remove();
            validateDates(parentForm);
        }
        if (event.target.closest('[data-add-override]')) {
            var template = document.getElementById('date-override-template');
            var container = document.querySelector('[data-overrides-container]');
            var index = 0;
            container.querySelectorAll('[data-override-date]').forEach(function (input) {
                var match = input.name.match(/^overrides\[(\d+)\]/);
                if (match) index = Math.max(index, Number(match[1]) + 1);
            });
            var holder = document.createElement('div');
            holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(index));
            var card = holder.firstElementChild;
            container.appendChild(card);
            updateGroup(card);
            card.querySelector('[data-override-date]').focus();
        }
    });

    // Deletion controls are also rendered by Yajra after each DataTables request.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.matches('[data-delete-form], .delete-form') && !window.confirm(form.dataset.confirm || 'Delete this record?')) event.preventDefault();
    });

    if (window.jQuery && window.jQuery.fn.DataTable) {
        window.jQuery.fn.dataTable.ext.errMode = 'none';
        document.querySelectorAll('[data-records-table]').forEach(function (element) {
            var businesses = element.dataset.recordsTable === 'businesses';
            var columns = businesses ? [
                { data: 'id', name: 'id' },
                { data: 'logo', name: 'logo', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'phone_number', name: 'phone_number' },
                { data: 'branches_count', name: 'branches_count', searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ] : [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'business_name', name: 'business_name' },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ];
            var error = element.parentElement.querySelector('.table-error');
            window.jQuery(element).on('error.dt dt-error.dt', function () { if (error) error.classList.remove('d-none'); });
            var instance = window.jQuery(element).DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: element.dataset.source, error: function () { if (error) error.classList.remove('d-none'); } },
                columns: columns,
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: {
                    search: '', searchPlaceholder: 'Search ' + (businesses ? 'businesses…' : 'branches…'),
                    lengthMenu: 'Show _MENU_ entries',
                    emptyTable: businesses ? 'No businesses yet. Add your first business to get started.' : 'No branches yet. Add a business, then create your first branch.',
                    zeroRecords: 'No matching records. Try another search.',
                    processing: 'Loading records…',
                    info: '_START_–_END_ of _TOTAL_ records',
                    infoEmpty: '0 records',
                    paginate: { previous: '←', next: '→' }
                }
            });
            window.jQuery(element).on('xhr.dt', function (event, settings, json) { if (error && json) error.classList.add('d-none'); });
            tableInstances.push({ instance: instance, live: !businesses });
        });
    }

    var liveCard = document.querySelector('[data-live-status]');
    var refreshing = false;
    function refreshStatus() {
        if (!liveCard || refreshing) return;
        refreshing = true;
        fetch(liveCard.dataset.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(function (response) { if (!response.ok) throw new Error('Status unavailable'); return response.json(); })
            .then(function (status) {
                var label = liveCard.querySelector('[data-status-label]');
                label.textContent = status.label;
                label.classList.toggle('is-open', status.is_open);
                label.classList.toggle('is-closed', !status.is_open);
                liveCard.querySelector('[data-status-date]').textContent = status.date;
                liveCard.querySelector('[data-status-override]').classList.toggle('d-none', status.source !== 'date_override');
                var slots = liveCard.querySelector('[data-today-slots]');
                slots.replaceChildren();
                if (!status.slots.length) {
                    var closed = document.createElement('span');
                    closed.className = 'text-secondary';
                    closed.textContent = 'Closed all day';
                    slots.appendChild(closed);
                }
                status.slots.forEach(function (slot) {
                    var pill = document.createElement('span');
                    pill.className = 'time-pill';
                    pill.textContent = status.source === 'date_override' && status.is_open ? 'Open all day · 00:00 – 24:00' : slot.start_time + ' – ' + slot.end_time;
                    slots.appendChild(pill);
                });
                var checked = new Intl.DateTimeFormat('en-IN', { timeZone: status.time_zone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).format(new Date(status.evaluated_at));
                liveCard.querySelector('[data-status-check]').textContent = 'Checked ' + checked + ' · ' + status.time_zone;
                // Date comes from the server. UTC is used only to find that date's weekday.
                var weekday = new Date(status.date + 'T12:00:00Z').getUTCDay() || 7;
                liveCard.querySelectorAll('[data-weekday]').forEach(function (row) { row.classList.toggle('is-today', Number(row.dataset.weekday) === weekday); });
                liveCard.querySelector('[data-status-error]').classList.add('d-none');
            })
            .catch(function () { liveCard.querySelector('[data-status-error]').classList.remove('d-none'); })
            .finally(function () { refreshing = false; });
    }

    function refreshLiveData() {
        if (document.hidden) return;
        refreshStatus();
        tableInstances.forEach(function (table) { if (table.live) table.instance.ajax.reload(null, false); });
    }
    window.setInterval(refreshLiveData, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshLiveData(); });
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            document.querySelectorAll('.submit-busy').forEach(function (button) { button.disabled = false; });
            window.location.reload();
        }
    });
}());
