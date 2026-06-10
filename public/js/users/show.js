"use strict";

// ============================================================
// Shared helpers
// ============================================================
function bidaCsrf() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function bidaRoute(template, id) {
      return template.replace(':id', id);
}

// Password show/hide (event-delegated)
function bidaInitPasswordToggles() {
      document.addEventListener('click', function (e) {
            const toggle = e.target.closest('.toggle-password');
            if (!toggle) return;

            const input = document.getElementById(toggle.getAttribute('data-target'));
            const icon = toggle.querySelector('i');
            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            if (icon) {
                  icon.classList.toggle('ki-eye', showing);
                  icon.classList.toggle('ki-eye-slash', !showing);
            }
      });
}

// ============================================================
// Generic server-side feed table (used by both tabs)
//   opts = { tableId, url, searchAttr, menuId, columns, filterKey, order }
//   filterKey is the data-filter attribute of the <select> (event|device).
// ============================================================
function BidaFeedTable(opts) {
      var table;
      var datatable;
      var menuEl;
      var fromPicker;
      var toPicker;

      var filters = {
            select: '',
            start_date: '',
            end_date: ''
      };

      var initDatatable = function () {
            datatable = $(table).DataTable({
                  processing: true,
                  serverSide: true,
                  order: opts.order || [],
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
                        onChange: function (dates) {
                              if (toPicker && dates.length) toPicker.set('minDate', dates[0]);
                        }
                  });
            }
            if (toEl && typeof flatpickr !== 'undefined') {
                  toPicker = flatpickr(toEl, {
                        dateFormat: 'd-m-Y', allowInput: false,
                        onChange: function (dates) {
                              if (fromPicker && dates.length) fromPicker.set('maxDate', dates[0]);
                        }
                  });
            }
      };

      var handleSearch = function () {
            var input = document.querySelector('[data-' + opts.searchAttr + '-filter="search"]');
            var timer;
            if (!input) return;
            input.addEventListener('keyup', function (e) {
                  clearTimeout(timer);
                  timer = setTimeout(function () {
                        datatable.search(e.target.value).draw();
                  }, 300);
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
            reload: function () {
                  if (datatable) datatable.ajax.reload();
            }
      };
}

// ============================================================
// Activity + Login feed tables
// ============================================================
var BidaUserActivityTable = (function () {
      return BidaFeedTable({
            tableId: BidaUserShowConfig.activityTableId,
            url: BidaUserShowConfig.activitiesUrl,
            searchAttr: BidaUserShowConfig.activitySearchAttr,
            menuId: BidaUserShowConfig.activityMenuId,
            filterKey: 'event',
            order: [],
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

var BidaUserLoginTable = (function () {
      return BidaFeedTable({
            tableId: BidaUserShowConfig.loginTableId,
            url: BidaUserShowConfig.loginActivitiesUrl,
            searchAttr: BidaUserShowConfig.loginSearchAttr,
            menuId: BidaUserShowConfig.loginMenuId,
            filterKey: 'device',
            order: [],
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
// Edit User modal (reused from list — reloads page on success)
// ============================================================
var BidaEditUser = (function () {
      const element = document.getElementById('kt_modal_edit_user');
      if (!element) return { init: function () { } };

      const form = element.querySelector('#kt_modal_edit_user_form');
      const modal = new bootstrap.Modal(element);
      const defaultPhoto = "url('" + BidaUserRoutes.placeholder + "')";

      let userId = null;
      let validator = null;
      let originalPhoto = defaultPhoto;

      var resetPhotoToOriginal = function () {
            const wrapper = document.querySelector('#kt_image_input_edit .image-input-wrapper');
            const fileInput = document.querySelector('#kt_image_input_edit input[name="photo"]');
            const removeInput = document.querySelector('#kt_image_input_edit input[name="remove_photo"]');
            const imageInput = document.querySelector('#kt_image_input_edit');
            if (wrapper) wrapper.style.backgroundImage = originalPhoto;
            if (fileInput) fileInput.value = '';
            if (removeInput) removeInput.value = '0';
            if (imageInput) {
                  imageInput.classList.remove('image-input-changed');
                  imageInput.classList.toggle('image-input-empty', originalPhoto === defaultPhoto);
            }
      };

      var initOpen = function () {
            document.addEventListener('click', function (e) {
                  const btn = e.target.closest('.edit-user-btn');
                  if (!btn) return;
                  e.preventDefault();
                  userId = btn.getAttribute('data-user-id');
                  if (!userId) return;
                  form.reset();

                  fetch(bidaRoute(BidaUserRoutes.json, userId), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => {
                              if (!data.success || !data.data) throw new Error(data.message || 'Invalid response');
                              const u = data.data;

                              document.getElementById('edit_user_id').value = u.id;
                              document.getElementById('kt_modal_edit_user_title').textContent = 'Update ' + u.name;
                              form.querySelector('[name="name"]').value = u.name || '';
                              form.querySelector('[name="designation"]').value = u.designation || '';
                              form.querySelector('[name="email"]').value = u.email || '';
                              form.querySelector('[name="mobile_number"]').value = u.mobile_number || '';

                              const roleRadio = form.querySelector('input[name="role"][value="' + u.role + '"]');
                              if (roleRadio) roleRadio.checked = true;

                              const wrapper = document.querySelector('#kt_image_input_edit .image-input-wrapper');
                              const imageInput = document.querySelector('#kt_image_input_edit');
                              if (u.photo_url) {
                                    originalPhoto = "url('" + u.photo_url + "')";
                                    wrapper.style.backgroundImage = originalPhoto;
                                    imageInput.classList.remove('image-input-empty');
                              } else {
                                    originalPhoto = defaultPhoto;
                                    wrapper.style.backgroundImage = defaultPhoto;
                                    imageInput.classList.add('image-input-empty');
                              }

                              modal.show();
                        })
                        .catch(err => toastr.error(err.message || 'Failed to load user details'));
            });

            ['cancel', 'close'].forEach(action => {
                  element.querySelector('[data-edit-users-modal-action="' + action + '"]')
                        ?.addEventListener('click', e => { e.preventDefault(); form.reset(); resetPhotoToOriginal(); modal.hide(); });
            });
      };

      var initPhoto = function () {
            const el = document.querySelector('#kt_image_input_edit');
            if (!el) return;
            const fileInput = el.querySelector('input[name="photo"]');
            const wrapper = el.querySelector('.image-input-wrapper');
            const removeInput = el.querySelector('input[name="remove_photo"]');

            fileInput.addEventListener('change', function () {
                  const file = this.files[0];
                  if (!file) return;
                  if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) { toastr.error('Please select a JPG or PNG image.'); this.value = ''; return; }
                  if (file.size > 100 * 1024) { toastr.error('Image size must be less than 100KB.'); this.value = ''; return; }
                  const reader = new FileReader();
                  reader.onload = function (e) {
                        wrapper.style.backgroundImage = "url('" + e.target.result + "')";
                        el.classList.remove('image-input-empty');
                        el.classList.add('image-input-changed');
                        removeInput.value = '0';
                  };
                  reader.readAsDataURL(file);
            });

            el.querySelector('[data-kt-image-input-action="cancel"]')?.addEventListener('click', function () {
                  wrapper.style.backgroundImage = originalPhoto; fileInput.value = ''; removeInput.value = '0';
                  el.classList.remove('image-input-changed');
            });
            el.querySelector('[data-kt-image-input-action="remove"]')?.addEventListener('click', function () {
                  wrapper.style.backgroundImage = defaultPhoto; fileInput.value = ''; removeInput.value = '1';
                  el.classList.remove('image-input-changed'); el.classList.add('image-input-empty');
            });
      };

      var initValidation = function () {
            validator = FormValidation.formValidation(form, {
                  fields: {
                        name: { validators: { notEmpty: { message: 'Name is required' } } },
                        email: { validators: { notEmpty: { message: 'Email is required' }, emailAddress: { message: 'Enter a valid email' } } },
                        mobile_number: { validators: { regexp: { regexp: /^01[3-9]\d{8}$/, message: 'Enter a valid Bangladeshi mobile number' } } },
                        role: { validators: { notEmpty: { message: 'Role is required' } } }
                  },
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' })
                  }
            });

            const submitBtn = element.querySelector('[data-edit-users-modal-action="submit"]');
            submitBtn.addEventListener('click', function (e) {
                  e.preventDefault();
                  validator.validate().then(function (status) {
                        if (status !== 'Valid') { toastr.warning('Please fill all required fields'); return; }

                        submitBtn.setAttribute('data-kt-indicator', 'on');
                        submitBtn.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', bidaCsrf());
                        formData.append('_method', 'PUT');

                        fetch(bidaRoute(BidaUserRoutes.update, userId), {
                              method: 'POST',
                              body: formData,
                              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                              .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw { message: data.message || 'Update failed', errors: data.errors };
                                    return data;
                              })
                              .then(data => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    if (data.success) {
                                          toastr.success(data.message || 'User updated');
                                          modal.hide();
                                          // Refresh page so the header card reflects the changes.
                                          setTimeout(function () { location.reload(); }, 600);
                                    } else { toastr.error(data.message || 'Update failed'); }
                              })
                              .catch(error => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    if (error.errors) Object.values(error.errors).forEach(msgs => toastr.error(msgs[0]));
                                    else toastr.error(error.message || 'Failed to update user');
                              });
                  });
            });
      };

      return { init: function () { initOpen(); initPhoto(); initValidation(); } };
})();

// ============================================================
// Reset Password modal (reused from list)
// ============================================================
var BidaResetPassword = (function () {
      const element = document.getElementById('kt_modal_edit_password');
      if (!element) return { init: function () { } };

      const form = element.querySelector('#kt_modal_edit_password_form');
      const modal = new bootstrap.Modal(element);
      let userId = null;
      let validator = null;

      var initOpen = function () {
            const passwordInput = document.getElementById('userPasswordNew');
            const strengthText = document.getElementById('password-strength-text');
            const strengthBar = document.getElementById('password-strength-bar');

            document.addEventListener('click', function (e) {
                  const btn = e.target.closest('.change-password-btn');
                  if (!btn) return;
                  userId = btn.getAttribute('data-user-id');
                  const name = btn.getAttribute('data-user-name');
                  const title = document.getElementById('kt_modal_edit_password_title');
                  if (title) title.textContent = 'Reset Password — ' + name;
            });

            ['cancel', 'close'].forEach(action => {
                  element.querySelector('[data-kt-edit-password-modal-action="' + action + '"]')
                        ?.addEventListener('click', e => {
                              e.preventDefault(); form.reset(); modal.hide();
                              if (strengthText) strengthText.textContent = '';
                              if (strengthBar) { strengthBar.className = 'progress-bar'; strengthBar.style.width = '0%'; }
                        });
            });

            if (passwordInput) {
                  passwordInput.addEventListener('input', function () {
                        const v = passwordInput.value;
                        let score = 0;
                        if (v.length >= 8) score++;
                        if (/[A-Z]/.test(v)) score++;
                        if (/[a-z]/.test(v)) score++;
                        if (/\d/.test(v)) score++;
                        if (/[^A-Za-z0-9]/.test(v)) score++;

                        const map = [
                              { t: 'Very Weak', c: 'bg-danger' },
                              { t: 'Very Weak', c: 'bg-danger' },
                              { t: 'Weak', c: 'bg-warning' },
                              { t: 'Moderate', c: 'bg-info' },
                              { t: 'Strong', c: 'bg-primary' },
                              { t: 'Very Strong', c: 'bg-success' }
                        ];
                        const m = map[score];
                        strengthText.textContent = v ? m.t : '';
                        strengthBar.className = 'progress-bar ' + m.c;
                        strengthBar.style.width = (score * 20) + '%';
                  });
            }
      };

      var initValidation = function () {
            validator = FormValidation.formValidation(form, {
                  fields: {
                        new_password: {
                              validators: {
                                    notEmpty: { message: 'Password is required' },
                                    stringLength: { min: 8, message: 'At least 8 characters' },
                                    regexp: { regexp: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/, message: 'Need uppercase, lowercase, number & special character' }
                              }
                        }
                  },
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' })
                  }
            });

            const submitBtn = element.querySelector('[data-kt-edit-password-modal-action="submit"]');
            submitBtn.addEventListener('click', function (e) {
                  e.preventDefault();
                  validator.validate().then(function (status) {
                        if (status !== 'Valid') { toastr.warning('Please enter a valid password'); return; }

                        submitBtn.setAttribute('data-kt-indicator', 'on'); submitBtn.disabled = true;

                        fetch(bidaRoute(BidaUserRoutes.resetPassword, userId), {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' },
                              body: JSON.stringify({ _method: 'PUT', new_password: document.getElementById('userPasswordNew').value })
                        })
                              .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || 'Update failed');
                                    return data;
                              })
                              .then(data => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    if (data.success) { toastr.success(data.message || 'Password updated'); modal.hide(); form.reset(); }
                                    else throw new Error(data.message || 'Update failed');
                              })
                              .catch(error => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    toastr.error(error.message || 'Failed to update password');
                              });
                  });
            });
      };

      return { init: function () { initOpen(); initValidation(); } };
})();

// ============================================================
// Tabs — reload the relevant table on switch (column sizing)
// ============================================================
var BidaUserShowTabs = (function () {
      return {
            init: function () {
                  $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                        const href = $(e.target).attr('href');
                        if (href === '#kt_tab_activity_log') BidaUserActivityTable.reload();
                        else if (href === '#kt_tab_login_activity') BidaUserLoginTable.reload();
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
                        const existing = bootstrap.Tooltip.getInstance(el);
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
      bidaInitPasswordToggles();
      BidaUserActivityTable.init();
      BidaUserLoginTable.init();
      BidaEditUser.init();
      BidaResetPassword.init();
      BidaUserShowTabs.init();
      BidaTooltips.init();
});