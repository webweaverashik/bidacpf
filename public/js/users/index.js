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

// Password show/hide (event-delegated, used by add + reset modals)
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
// Users List (server-side DataTable)
// ============================================================
var BidaUsersList = (function () {
      var table;
      var datatable;

      var filters = { role: '', deleted_only: false };

      var initDatatable = function () {
            datatable = $(table).DataTable({
                  processing: true,
                  serverSide: true,
                  ajax: {
                        url: BidaUserRoutes.data,
                        type: 'GET',
                        data: function (d) {
                              d.role = filters.role;
                              d.deleted_only = filters.deleted_only ? 'true' : 'false';
                        }
                  },
                  columns: [
                        { data: 'counter', name: 'counter', orderable: false, searchable: false },
                        { data: 'user_info', name: 'name' },
                        { data: 'email', name: 'email' },
                        { data: 'mobile', name: 'mobile_number' },
                        { data: 'role', name: 'role', orderable: false },
                        { data: 'last_login', name: 'last_login', orderable: false, searchable: false },
                        { data: 'active', name: 'is_active', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
                  ],
                  order: [],
                  pageLength: 10,
                  lengthMenu: [10, 25, 50, 100],
                  language: {
                        processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"></div></div>',
                        emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-people fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No users found</span></div>',
                        zeroRecords: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-people fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No matching users found</span></div>'
                  },
                  drawCallback: function () {
                        BidaTooltips.init();
                  }
            });
      };

      var handleSearch = function () {
            const input = document.querySelector('[data-kt-user-table-filter="search"]');
            if (!input) return;
            var timer;
            input.addEventListener('keyup', function (e) {
                  clearTimeout(timer);
                  timer = setTimeout(function () {
                        datatable.search(e.target.value).draw();
                  }, 300);
            });
      };

      var handleFilter = function () {
            const form = document.querySelector('[data-users-table-filter="form"]');
            if (!form) return;

            const roleSelect = form.querySelector('[data-users-table-filter="role"]');
            const filterBtn = form.querySelector('[data-users-table-filter="filter"]');
            const resetBtn = form.querySelector('[data-users-table-filter="reset"]');

            if (filterBtn) {
                  filterBtn.addEventListener('click', function () {
                        filters.role = $(roleSelect).val() || '';
                        datatable.ajax.reload();
                  });
            }
            if (resetBtn) {
                  resetBtn.addEventListener('click', function () {
                        $(roleSelect).val(null).trigger('change');
                        filters.role = '';
                        datatable.ajax.reload();
                  });
            }
      };

      var handleDeletedToggle = function () {
            const toggle = document.getElementById('show_deleted_only');
            if (!toggle) return;
            toggle.addEventListener('change', function () {
                  filters.deleted_only = this.checked;
                  datatable.ajax.reload();
            });
      };

      var handleToggleActivation = function () {
            document.addEventListener('change', function (e) {
                  const toggle = e.target.closest('.toggle-active');
                  if (!toggle) return;

                  fetch(BidaUserRoutes.toggleActive, {
                        method: 'POST',
                        headers: {
                              'Content-Type': 'application/json',
                              'X-CSRF-TOKEN': bidaCsrf(),
                              'Accept': 'application/json'
                        },
                        body: JSON.stringify({ user_id: toggle.value, is_active: toggle.checked ? 1 : 0 })
                  })
                        .then(r => r.json())
                        .then(data => {
                              if (data.success) {
                                    toastr.success(data.message);
                              } else {
                                    toastr.error(data.message);
                                    toggle.checked = !toggle.checked;
                              }
                        })
                        .catch(() => {
                              toastr.error('Error updating user status');
                              toggle.checked = !toggle.checked;
                        });
            });
      };

      return {
            init: function () {
                  table = document.getElementById('kt_users_table');
                  if (!table) return;
                  initDatatable();
                  handleSearch();
                  handleFilter();
                  handleDeletedToggle();
                  handleToggleActivation();
            },
            reload: function () {
                  if (datatable) datatable.ajax.reload(null, false);
            }
      };
})();

// ============================================================
// Add User
// ============================================================
var BidaAddUser = (function () {
      const element = document.getElementById('kt_modal_add_user');
      if (!element) return { init: function () { } };

      const form = element.querySelector('#kt_modal_add_user_form');
      const modal = new bootstrap.Modal(element);
      const defaultPhoto = "url('" + BidaUserRoutes.placeholder + "')";
      var validator;

      var resetPhoto = function () {
            const wrapper = document.querySelector('#kt_image_input_add .image-input-wrapper');
            const fileInput = document.querySelector('#kt_image_input_add input[name="photo"]');
            const imageInput = document.querySelector('#kt_image_input_add');
            if (wrapper) wrapper.style.backgroundImage = defaultPhoto;
            if (fileInput) fileInput.value = '';
            if (imageInput) {
                  imageInput.classList.remove('image-input-changed');
                  imageInput.classList.add('image-input-empty');
            }
      };

      var initPhoto = function () {
            const el = document.querySelector('#kt_image_input_add');
            if (!el) return;
            const fileInput = el.querySelector('input[name="photo"]');
            const wrapper = el.querySelector('.image-input-wrapper');

            fileInput.addEventListener('change', function () {
                  const file = this.files[0];
                  if (!file) return;
                  if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
                        toastr.error('Please select a JPG or PNG image.'); this.value = ''; return;
                  }
                  if (file.size > 100 * 1024) {
                        toastr.error('Image size must be less than 100KB.'); this.value = ''; return;
                  }
                  const reader = new FileReader();
                  reader.onload = function (e) {
                        wrapper.style.backgroundImage = "url('" + e.target.result + "')";
                        el.classList.remove('image-input-empty');
                        el.classList.add('image-input-changed');
                  };
                  reader.readAsDataURL(file);
            });

            el.querySelector('[data-kt-image-input-action="cancel"]')?.addEventListener('click', resetPhoto);
            el.querySelector('[data-kt-image-input-action="remove"]')?.addEventListener('click', resetPhoto);
      };

      var initValidation = function () {
            validator = FormValidation.formValidation(form, {
                  fields: {
                        name: { validators: { notEmpty: { message: 'Name is required' } } },
                        email: { validators: { notEmpty: { message: 'Email is required' }, emailAddress: { message: 'Enter a valid email' } } },
                        mobile_number: {
                              validators: {
                                    regexp: { regexp: /^01[3-9]\d{8}$/, message: 'Enter a valid Bangladeshi mobile number' }
                              }
                        },
                        role: { validators: { notEmpty: { message: 'Role is required' } } },
                        password: {
                              validators: {
                                    notEmpty: { message: 'Password is required' },
                                    stringLength: { min: 8, message: 'At least 8 characters' }
                              }
                        },
                        password_confirmation: {
                              validators: {
                                    notEmpty: { message: 'Please confirm the password' },
                                    identical: { compare: function () { return form.querySelector('[name="password"]').value; }, message: 'Passwords do not match' }
                              }
                        }
                  },
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' })
                  }
            });

            const submitBtn = element.querySelector('[data-add-users-modal-action="submit"]');
            submitBtn.addEventListener('click', function (e) {
                  e.preventDefault();
                  validator.validate().then(function (status) {
                        if (status !== 'Valid') { toastr.warning('Please fill all fields correctly'); return; }

                        submitBtn.setAttribute('data-kt-indicator', 'on');
                        submitBtn.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', bidaCsrf());

                        fetch(BidaUserRoutes.store, {
                              method: 'POST',
                              body: formData,
                              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                              .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw { message: data.message || 'User creation failed', errors: data.errors };
                                    return data;
                              })
                              .then(data => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    if (data.success) {
                                          toastr.success(data.message || 'User created');
                                          modal.hide(); form.reset(); resetPhoto();
                                          BidaUsersList.reload();
                                    } else {
                                          toastr.error(data.message || 'User creation failed');
                                    }
                              })
                              .catch(error => {
                                    submitBtn.removeAttribute('data-kt-indicator'); submitBtn.disabled = false;
                                    if (error.errors) {
                                          Object.values(error.errors).forEach(msgs => toastr.error(msgs[0]));
                                    } else {
                                          toastr.error(error.message || 'Failed to create user');
                                    }
                              });
                  });
            });
      };

      var initButtons = function () {
            ['cancel', 'close'].forEach(action => {
                  const btn = element.querySelector('[data-add-users-modal-action="' + action + '"]');
                  btn?.addEventListener('click', e => {
                        e.preventDefault(); form.reset(); resetPhoto(); modal.hide();
                  });
            });
      };

      return {
            init: function () { initButtons(); initPhoto(); initValidation(); }
      };
})();

// ============================================================
// Edit User
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
                                          toastr.success(data.message || 'User updated'); modal.hide(); BidaUsersList.reload();
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
// Reset Password
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
// Delete User (two-step)
// ============================================================
var BidaDeleteUser = (function () {
      const modalElement = document.getElementById('kt_modal_delete_user');
      if (!modalElement) return { init: function () { } };

      const modal = new bootstrap.Modal(modalElement);
      let currentId = null, currentName = null;

      const stepWarning = document.getElementById('delete_step_warning');
      const stepConfirm = document.getElementById('delete_step_confirm');
      const confirmInput = document.getElementById('delete_confirm_input');
      const btnConfirm = document.getElementById('btn_delete_confirm');

      var reset = function () {
            stepWarning.style.display = 'block';
            stepConfirm.style.display = 'none';
            confirmInput.value = '';
            confirmInput.classList.remove('is-valid', 'is-invalid');
            btnConfirm.disabled = true;
      };

      return {
            init: function () {
                  document.addEventListener('click', function (e) {
                        const btn = e.target.closest('.delete-user');
                        if (!btn) return;
                        e.preventDefault();
                        currentId = btn.getAttribute('data-user-id');
                        currentName = btn.getAttribute('data-user-name');

                        document.getElementById('delete_user_name').textContent = currentName || 'Unknown';
                        document.getElementById('delete_user_email').textContent = btn.getAttribute('data-user-email') || '';
                        document.getElementById('delete_user_name_confirm').textContent = currentName || 'Unknown';
                        document.getElementById('delete_user_photo').src = btn.getAttribute('data-user-photo') || BidaUserRoutes.placeholder;

                        reset(); modal.show();
                  });

                  document.getElementById('btn_delete_proceed').addEventListener('click', function () {
                        stepWarning.style.display = 'none';
                        stepConfirm.style.display = 'block';
                        confirmInput.focus();
                  });

                  document.getElementById('btn_delete_back').addEventListener('click', reset);

                  confirmInput.addEventListener('input', function () {
                        const valid = this.value.trim() === 'DELETE';
                        this.classList.toggle('is-valid', valid);
                        this.classList.toggle('is-invalid', this.value.length > 0 && !valid);
                        btnConfirm.disabled = !valid;
                  });

                  btnConfirm.addEventListener('click', function () {
                        btnConfirm.setAttribute('data-kt-indicator', 'on');
                        btnConfirm.disabled = true;
                        const deletedName = currentName;

                        fetch(bidaRoute(BidaUserRoutes.destroy, currentId), {
                              method: 'DELETE',
                              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' }
                        })
                              .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || 'Delete failed');
                                    return data;
                              })
                              .then(data => {
                                    btnConfirm.removeAttribute('data-kt-indicator');
                                    if (data.success) {
                                          modal.hide();
                                          Swal.fire({
                                                icon: 'success', title: 'User Deleted',
                                                html: '<p class="mb-2"><strong>' + deletedName + '</strong> has been deleted.</p><p class="text-muted fs-7">The account is hidden and can be recovered by an administrator.</p>',
                                                confirmButtonText: 'OK', buttonsStyling: false,
                                                customClass: { confirmButton: 'btn btn-primary' }
                                          }).then(() => BidaUsersList.reload());
                                    } else throw new Error(data.message || 'Delete failed');
                              })
                              .catch(error => {
                                    btnConfirm.removeAttribute('data-kt-indicator'); btnConfirm.disabled = false;
                                    toastr.error(error.message || 'An error occurred while deleting');
                              });
                  });

                  modalElement.addEventListener('hidden.bs.modal', function () {
                        reset(); currentId = null; currentName = null;
                  });
            }
      };
})();

// ============================================================
// Recover User
// ============================================================
var BidaRecoverUser = (function () {
      const modalElement = document.getElementById('kt_modal_recover_user');
      if (!modalElement) return { init: function () { } };

      const modal = new bootstrap.Modal(modalElement);
      let currentId = null;
      const btnConfirm = document.getElementById('btn_recover_confirm');

      return {
            init: function () {
                  document.addEventListener('click', function (e) {
                        const btn = e.target.closest('.recover-user-btn');
                        if (!btn) return;
                        e.preventDefault();
                        currentId = btn.getAttribute('data-user-id');
                        document.getElementById('recover_user_name').textContent = btn.getAttribute('data-user-name') || 'Unknown';
                        modal.show();
                  });

                  btnConfirm.addEventListener('click', function () {
                        btnConfirm.setAttribute('data-kt-indicator', 'on'); btnConfirm.disabled = true;

                        fetch(BidaUserRoutes.recover, {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': bidaCsrf(), 'Accept': 'application/json' },
                              body: JSON.stringify({ user_id: currentId })
                        })
                              .then(async response => {
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || 'Recover failed');
                                    return data;
                              })
                              .then(data => {
                                    btnConfirm.removeAttribute('data-kt-indicator'); btnConfirm.disabled = false;
                                    if (data.success) { modal.hide(); toastr.success(data.message); BidaUsersList.reload(); }
                                    else throw new Error(data.message || 'Recover failed');
                              })
                              .catch(error => {
                                    btnConfirm.removeAttribute('data-kt-indicator'); btnConfirm.disabled = false;
                                    toastr.error(error.message || 'An error occurred while recovering');
                              });
                  });

                  modalElement.addEventListener('hidden.bs.modal', function () { currentId = null; });
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
      BidaUsersList.init();
      BidaAddUser.init();
      BidaEditUser.init();
      BidaResetPassword.init();
      BidaDeleteUser.init();
      BidaRecoverUser.init();
      BidaTooltips.init();
});