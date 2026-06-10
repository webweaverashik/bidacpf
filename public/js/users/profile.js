"use strict";

// ============================================================
// Shared helpers
// ============================================================
function bidaCsrf() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// ============================================================
// Generic server-side feed table (Activity + Login)
//   opts = { tableId, url, searchAttr, menuId, columns, filterKey }
// ============================================================
function BidaFeedTable(opts) {
    var table;
    var datatable;
    var menuEl;
    var fromPicker;
    var toPicker;

    var filters = { select: '', start_date: '', end_date: '' };

    var initDatatable = function () {
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            order: [],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: opts.url,
                type: 'GET',
                data: function (d) {
                    if (opts.filterKey) d[opts.filterKey] = filters.select;
                    d.start_date = filters.start_date;
                    d.end_date = filters.end_date;
                }
            },
            columns: opts.columns,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"></div></div>',
                emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-information-5 fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No records found</span></div>',
                zeroRecords: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-information-5 fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No matching records found</span></div>'
            }
        });
    };

    var initFlatpickr = function () {
        var fromEl = document.querySelector('[data-' + opts.searchAttr + '-date="from"]');
        var toEl = document.querySelector('[data-' + opts.searchAttr + '-date="to"]');
        if (fromEl && typeof flatpickr !== 'undefined') {
            fromPicker = flatpickr(fromEl, {
                dateFormat: 'd-m-Y', allowInput: false,
                onChange: function (dates) { if (toPicker && dates.length) toPicker.set('minDate', dates[0]); }
            });
        }
        if (toEl && typeof flatpickr !== 'undefined') {
            toPicker = flatpickr(toEl, {
                dateFormat: 'd-m-Y', allowInput: false,
                onChange: function (dates) { if (fromPicker && dates.length) fromPicker.set('maxDate', dates[0]); }
            });
        }
    };

    var handleSearch = function () {
        var input = document.querySelector('[data-' + opts.searchAttr + '-filter="search"]');
        var timer;
        if (!input) return;
        input.addEventListener('keyup', function (e) {
            clearTimeout(timer);
            timer = setTimeout(function () { datatable.search(e.target.value).draw(); }, 300);
        });
    };

    var closeMenu = function () {
        if (menuEl && typeof KTMenu !== 'undefined') {
            var inst = KTMenu.getInstance(menuEl);
            if (inst) inst.hide();
        }
    };

    var handleFilter = function () {
        var form = document.querySelector('[data-' + opts.searchAttr + '-filter="form"]');
        if (!form) return;

        var selectEl = opts.filterKey
            ? form.querySelector('[data-' + opts.searchAttr + '-filter="' + opts.filterKey + '"]')
            : null;
        var applyBtn = form.querySelector('[data-' + opts.searchAttr + '-filter="filter"]');
        var resetBtn = form.querySelector('[data-' + opts.searchAttr + '-filter="reset"]');

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                filters.select = selectEl ? ($(selectEl).val() || '') : '';
                var fromEl = document.querySelector('[data-' + opts.searchAttr + '-date="from"]');
                var toEl = document.querySelector('[data-' + opts.searchAttr + '-date="to"]');
                filters.start_date = fromEl ? fromEl.value : '';
                filters.end_date = toEl ? toEl.value : '';
                datatable.ajax.reload();
                closeMenu();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (selectEl) $(selectEl).val(null).trigger('change');
                if (fromPicker) { fromPicker.clear(); fromPicker.set('maxDate', null); }
                if (toPicker) { toPicker.clear(); toPicker.set('minDate', null); }
                filters.select = '';
                filters.start_date = '';
                filters.end_date = '';
                var fromEl = document.querySelector('[data-' + opts.searchAttr + '-date="from"]');
                var toEl = document.querySelector('[data-' + opts.searchAttr + '-date="to"]');
                if (fromEl) fromEl.value = '';
                if (toEl) toEl.value = '';
                datatable.ajax.reload();
                closeMenu();
            });
        }
    };

    return {
        init: function () {
            table = document.getElementById(opts.tableId);
            menuEl = document.getElementById(opts.menuId);
            if (!table) return;
            initDatatable();
            initFlatpickr();
            handleSearch();
            handleFilter();
        },
        reload: function () { if (datatable) datatable.ajax.reload(); }
    };
}

// ============================================================
// Activity + Login feed tables
// ============================================================
var BidaProfileActivityTable = (function () {
    return BidaFeedTable({
        tableId: ProfileConfig.activityTableId,
        url: ProfileConfig.activitiesUrl,
        searchAttr: ProfileConfig.activitySearchAttr,
        menuId: ProfileConfig.activityMenuId,
        filterKey: 'event',
        columns: [
            { data: 'counter', name: 'counter', orderable: false, searchable: false },
            { data: 'module', name: 'log_name', orderable: false },
            { data: 'event', name: 'event', orderable: false },
            { data: 'description', name: 'description', orderable: false },
            { data: 'changes', name: 'changes', orderable: false, searchable: false },
            { data: 'time', name: 'created_at', orderable: false }
        ]
    });
})();

var BidaProfileLoginTable = (function () {
    return BidaFeedTable({
        tableId: ProfileConfig.loginTableId,
        url: ProfileConfig.loginActivitiesUrl,
        searchAttr: ProfileConfig.loginSearchAttr,
        menuId: ProfileConfig.loginMenuId,
        filterKey: 'device',
        columns: [
            { data: 'counter', name: 'counter', orderable: false, searchable: false },
            { data: 'ip_address', name: 'ip_address', orderable: false },
            { data: 'user_agent', name: 'user_agent', orderable: false },
            { data: 'device', name: 'device', orderable: false },
            { data: 'time', name: 'created_at', orderable: false }
        ]
    });
})();

// ============================================================
// Password toggles (event-delegated)
// ============================================================
var BidaPasswordToggles = (function () {
    return {
        init: function () {
            document.querySelectorAll('.toggle-password').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    var input = document.getElementById(this.getAttribute('data-target'));
                    var icon = this.querySelector('i');
                    if (!input) return;
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) icon.classList.replace('ki-eye', 'ki-eye-slash');
                    } else {
                        input.type = 'password';
                        if (icon) icon.classList.replace('ki-eye-slash', 'ki-eye');
                    }
                });
            });
        }
    };
})();

// ============================================================
// Change Password Modal
// ============================================================
var BidaPasswordModal = (function () {
    var modal, modalElement, form, submitButton, newInput, confirmInput, strengthText, strengthBar;

    var calc = function (pw) {
        if (!pw) return { score: 0, text: '', color: '', width: '0%' };
        var s = 0;
        if (pw.length >= 8) s++;
        if (pw.length >= 12) s++;
        if (/[a-z]/.test(pw)) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^a-zA-Z0-9]/.test(pw)) s++;
        if (s <= 2) return { score: s, text: 'Weak', color: 'bg-danger', width: '25%' };
        if (s <= 4) return { score: s, text: 'Fair', color: 'bg-warning', width: '50%' };
        if (s === 5) return { score: s, text: 'Good', color: 'bg-info', width: '75%' };
        return { score: s, text: 'Very Strong', color: 'bg-success', width: '100%' };
    };

    // Returns true if password meets the server regex
    // (>=8, lower, upper, digit, special).
    var meetsPolicy = function (pw) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/.test(pw);
    };

    var updateMeter = function () {
        var st = calc(newInput.value);
        strengthText.textContent = st.text;
        strengthText.className = st.text ? 'fw-bold fs-5 mb-2 text-' + st.color.split('-')[1] : 'fw-bold fs-5 mb-2';
        strengthBar.className = 'progress-bar ' + st.color;
        strengthBar.style.width = st.width;
        submitButton.disabled = !meetsPolicy(newInput.value);
    };

    var submit = function () {
        if (!meetsPolicy(newInput.value)) {
            toastr.warning('Password does not meet the required policy.');
            return;
        }
        if (newInput.value !== confirmInput.value) {
            confirmInput.classList.add('is-invalid');
            toastr.error('Passwords do not match.');
            return;
        }
        confirmInput.classList.remove('is-invalid');

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        fetch(ProfileConfig.passwordResetUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' },
            body: JSON.stringify({
                new_password: newInput.value,
                new_password_confirmation: confirmInput.value
            })
        })
            .then(async r => { const d = await r.json(); if (!r.ok) throw d; return d; })
            .then(function (data) {
                submitButton.removeAttribute('data-kt-indicator');
                if (data.success) {
                    modal.hide();
                    Swal.fire({
                        text: data.message || 'Password updated successfully!', icon: 'success',
                        buttonsStyling: false, confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                } else {
                    submitButton.disabled = false;
                    toastr.error(data.message || 'Password update failed.');
                }
            })
            .catch(function (err) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
                if (err && err.errors) Object.values(err.errors).forEach(m => toastr.error(m[0]));
                else toastr.error((err && err.message) || 'Something went wrong.');
            });
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_password');
            if (!modalElement) return;
            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_password_form');
            submitButton = document.getElementById('btn_submit_password');
            newInput = document.getElementById('modal_password_new');
            confirmInput = document.getElementById('modal_password_confirm');
            strengthText = document.getElementById('modal_password_strength_text');
            strengthBar = document.getElementById('modal_password_strength_bar');

            newInput.addEventListener('input', updateMeter);
            confirmInput.addEventListener('input', function () {
                confirmInput.classList.toggle('is-invalid', confirmInput.value && newInput.value !== confirmInput.value);
            });

            form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });

            modalElement.addEventListener('hidden.bs.modal', function () {
                form.reset();
                strengthText.textContent = '';
                strengthBar.style.width = '0%';
                strengthBar.className = 'progress-bar';
                submitButton.disabled = true;
                confirmInput.classList.remove('is-invalid');
            });

            var open = document.getElementById('btn_change_password');
            if (open) open.addEventListener('click', function () { modal.show(); });
        }
    };
})();

// ============================================================
// Reusable image-input handler (preview / cancel / remove)
// ============================================================
function bidaInitImageInput(uploadElId, fileInputId, removeInputId, originalUrl) {
    var el = document.getElementById(uploadElId);
    if (!el) return null;
    var wrapper = el.querySelector('.image-input-wrapper');
    var fileInput = document.getElementById(fileInputId);
    var removeInput = document.getElementById(removeInputId);

    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
                toastr.error('Please select a valid image file (JPG, PNG)'); fileInput.value = ''; return;
            }
            if (file.size > 100 * 1024) {
                toastr.error('Image size must be less than 100KB'); fileInput.value = ''; return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                wrapper.style.backgroundImage = "url('" + ev.target.result + "')";
                el.classList.add('image-input-changed');
                el.classList.remove('image-input-empty');
                if (removeInput) removeInput.value = '0';
            };
            reader.readAsDataURL(file);
        });
    }

    el.querySelector('[data-kt-image-input-action="cancel"]')?.addEventListener('click', function () {
        if (fileInput) fileInput.value = '';
        wrapper.style.backgroundImage = "url('" + originalUrl + "')";
        el.classList.remove('image-input-changed');
        if (removeInput) removeInput.value = '0';
    });

    el.querySelector('[data-kt-image-input-action="remove"]')?.addEventListener('click', function () {
        if (fileInput) fileInput.value = '';
        wrapper.style.backgroundImage = "url('" + ProfileConfig.placeholderUrl + "')";
        el.classList.remove('image-input-changed');
        el.classList.add('image-input-empty');
        if (removeInput) removeInput.value = '1';
    });

    return {
        reset: function () {
            if (fileInput) fileInput.value = '';
            wrapper.style.backgroundImage = "url('" + originalUrl + "')";
            el.classList.remove('image-input-changed', 'image-input-empty');
            if (removeInput) removeInput.value = '0';
        }
    };
}

// ============================================================
// Photo Modal (non-admin)
// ============================================================
var BidaPhotoModal = (function () {
    var modal, modalElement, form, submitButton, imageCtl;

    var submit = function () {
        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        fetch(ProfileConfig.profileUpdateUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' },
            body: new FormData(form)
        })
            .then(async r => { const d = await r.json(); if (!r.ok) throw d; return d; })
            .then(function (data) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
                if (data.success) {
                    modal.hide();
                    Swal.fire({
                        text: data.message || 'Photo updated successfully!', icon: 'success',
                        buttonsStyling: false, confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    }).then(function () { location.reload(); });
                } else {
                    toastr.error(data.message || 'Photo update failed.');
                }
            })
            .catch(function (err) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
                toastr.error((err && err.message) || 'Something went wrong.');
            });
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_photo');
            if (!modalElement) return;
            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_photo_form');
            submitButton = document.getElementById('btn_submit_photo');

            imageCtl = bidaInitImageInput('kt_photo_upload', 'photo_input', 'photo_remove', ProfileConfig.userPhotoUrl);

            form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
            modalElement.addEventListener('hidden.bs.modal', function () { if (imageCtl) imageCtl.reset(); });

            var open = document.getElementById('btn_change_photo');
            if (open) open.addEventListener('click', function () { modal.show(); });
        }
    };
})();

// ============================================================
// Profile Modal (admin)
// ============================================================
var BidaProfileModal = (function () {
    var modal, modalElement, form, submitButton, imageCtl;
    var original = {};

    var store = function () {
        original = {
            name: val('profile_name'),
            designation: val('profile_designation'),
            email: val('profile_email'),
            mobile_number: val('profile_mobile')
        };
    };

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    var changed = function () {
        var photoEl = document.getElementById('profile_photo_input');
        var removeEl = document.getElementById('profile_photo_remove');
        var photoChanged = (photoEl && photoEl.files.length > 0) || (removeEl && removeEl.value === '1');
        return val('profile_name') !== original.name
            || val('profile_designation') !== original.designation
            || val('profile_email') !== original.email
            || val('profile_mobile') !== original.mobile_number
            || photoChanged;
    };

    var showError = function (field, message) {
        var input = form.querySelector('[name="' + field + '"]');
        if (input) {
            input.classList.add('is-invalid');
            var fb = input.parentElement.querySelector('.invalid-feedback');
            if (fb) fb.textContent = message;
        }
        toastr.error(message);
    };

    var clearErrors = function () {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    };

    var validate = function () {
        clearErrors();
        var ok = true;
        if (!val('profile_name')) { showError('name', 'Name is required.'); ok = false; }
        var email = val('profile_email');
        if (!email) { showError('email', 'Email is required.'); ok = false; }
        else if (!/^\S+@\S+\.\S+$/.test(email)) { showError('email', 'Please enter a valid email address.'); ok = false; }
        var mobile = val('profile_mobile');
        if (mobile && !/^01[3-9]\d{8}$/.test(mobile)) {
            showError('mobile_number', 'Please enter a valid 11-digit Bangladeshi mobile number.'); ok = false;
        }
        return ok;
    };

    var submit = function () {
        if (!changed()) { toastr.info('No changes detected.'); return; }
        if (!validate()) return;

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        fetch(ProfileConfig.profileUpdateUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' },
            body: new FormData(form)
        })
            .then(async r => { const d = await r.json(); if (!r.ok) throw d; return d; })
            .then(function (data) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
                if (data.success) {
                    modal.hide();
                    Swal.fire({
                        text: data.message || 'Profile updated successfully!', icon: 'success',
                        buttonsStyling: false, confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    }).then(function () { location.reload(); });
                } else if (data.errors) {
                    Object.keys(data.errors).forEach(f => showError(f, data.errors[f][0]));
                } else {
                    toastr.error(data.message || 'Profile update failed.');
                }
            })
            .catch(function (err) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
                if (err && err.errors) Object.keys(err.errors).forEach(f => showError(f, err.errors[f][0]));
                else toastr.error((err && err.message) || 'Something went wrong.');
            });
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_profile');
            if (!modalElement) return;
            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_profile_form');
            submitButton = document.getElementById('btn_submit_profile');

            store();
            imageCtl = bidaInitImageInput('kt_profile_photo_upload', 'profile_photo_input', 'profile_photo_remove', ProfileConfig.userPhotoUrl);

            form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });

            modalElement.addEventListener('hidden.bs.modal', function () {
                document.getElementById('profile_name').value = original.name;
                document.getElementById('profile_designation').value = original.designation;
                document.getElementById('profile_email').value = original.email;
                document.getElementById('profile_mobile').value = original.mobile_number;
                if (imageCtl) imageCtl.reset();
                clearErrors();
            });

            var open = document.getElementById('btn_edit_profile');
            if (open) open.addEventListener('click', function () { store(); modal.show(); });
        }
    };
})();

// ============================================================
// Tabs — reload table on switch
// ============================================================
var BidaProfileTabs = (function () {
    return {
        init: function () {
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                var href = $(e.target).attr('href');
                if (href === '#kt_tab_activity_log') BidaProfileActivityTable.reload();
                else if (href === '#kt_tab_login_activity') BidaProfileLoginTable.reload();
            });
        }
    };
})();

// ============================================================
// Tooltips
// ============================================================
var BidaTooltips = (function () {
    return {
        init: function () {
            [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (el) {
                var existing = bootstrap.Tooltip.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Tooltip(el);
            });
        }
    };
})();

// ============================================================
// Init
// ============================================================
KTUtil.onDOMContentLoaded(function () {
    BidaProfileActivityTable.init();
    BidaProfileLoginTable.init();
    BidaPasswordToggles.init();
    BidaPasswordModal.init();
    BidaPhotoModal.init();
    BidaProfileModal.init();
    BidaProfileTabs.init();
    BidaTooltips.init();
});