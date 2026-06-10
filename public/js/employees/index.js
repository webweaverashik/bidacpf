"use strict";

// ======================================== 
// Users List DataTable (AJAX Server-Side)
// ========================================
var KTUsersList = function () {
    var table;
    var datatable;

    // Filter values
    var filters = {
        branch: '',
        role: '',
        deleted_only: false
    };

    var initDatatable = function () {
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routeGetUsers,
                type: 'GET',
                data: function (d) {
                    d.branch = filters.branch;
                    d.role = filters.role;
                    d.deleted_only = filters.deleted_only ? 'true' : 'false';
                }
            },
            columns: [
                { data: 'counter', name: 'counter', orderable: false, searchable: false },
                { data: 'user_info', name: 'name' },
                { data: 'mobile', name: 'mobile_number' },
                { data: 'branch', name: 'branch' },
                { data: 'role', name: 'role', orderable: false },
                { data: 'last_login', name: 'last_login', orderable: false },
                { data: 'active', name: 'is_active', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-people fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No users found</span></div>',
                zeroRecords: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-people fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No matching users found</span></div>'
            },
            drawCallback: function () {
                // Reinitialize tooltips after table redraw
                KTTooltips.init();
            }
        });

        // Re-init functions on every table re-draw
        datatable.on('draw', function () {
            // Tooltips handled in drawCallback
        });
    };

    // Search Datatable
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-user-table-filter="search"]');
        var searchTimer;

        if (filterSearch) {
            filterSearch.addEventListener('keyup', function (e) {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    datatable.search(e.target.value).draw();
                }, 300);
            });
        }
    };

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-users-table-filter="form"]');
        if (!filterForm) return;

        const filterButton = filterForm.querySelector('[data-users-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-users-table-filter="reset"]');
        const branchSelect = filterForm.querySelector('[data-users-table-filter="branch"]');
        const roleSelect = filterForm.querySelector('[data-users-table-filter="role"]');

        // Filter datatable on submit
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                filters.branch = $(branchSelect).val() || '';
                filters.role = $(roleSelect).val() || '';
                datatable.ajax.reload();
            });
        }

        // Reset datatable
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                $(branchSelect).val(null).trigger('change');
                $(roleSelect).val(null).trigger('change');
                filters.branch = '';
                filters.role = '';
                datatable.ajax.reload();
            });
        }
    };

    // Handle "Show Deleted Only" toggle
    var handleDeletedToggle = function () {
        const deletedToggle = document.getElementById('show_deleted_only');
        if (!deletedToggle) return;

        deletedToggle.addEventListener('change', function () {
            filters.deleted_only = this.checked;
            datatable.ajax.reload();
        });
    };

    // Toggle activation
    var handleToggleActivation = function () {
        document.addEventListener('change', function (e) {
            const toggle = e.target.closest('.toggle-active');
            if (!toggle) return;

            const userId = toggle.value;
            const isActive = toggle.checked ? 1 : 0;

            fetch(routeToggleActive, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                },
                body: JSON.stringify({
                    user_id: userId,
                    is_active: isActive
                })
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                    } else {
                        toastr.error(data.message);
                        toggle.checked = !toggle.checked; // Revert toggle
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error occurred while toggling user status');
                    toggle.checked = !toggle.checked; // Revert toggle
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
            if (datatable) {
                datatable.ajax.reload();
            }
        }
    };
}();

// ======================================== 
// Add User Modal
// ========================================
var KTUsersAddUser = function () {
    const element = document.getElementById('kt_modal_add_user');
    if (!element) return { init: function () { } };

    const form = element.querySelector('#kt_modal_add_user_form');
    const modal = new bootstrap.Modal(element);
    const defaultPhotoUrl = document.querySelector('#kt_image_input_add .image-input-wrapper')?.style.backgroundImage || "url('/img/male-placeholder.png')";

    var validator;

    // Reset photo to default
    var resetPhotoToDefault = function () {
        const wrapper = document.querySelector('#kt_image_input_add .image-input-wrapper');
        const fileInput = document.querySelector('#kt_image_input_add input[name="user_photo"]');
        const imageInput = document.querySelector('#kt_image_input_add');

        if (wrapper) {
            wrapper.style.backgroundImage = defaultPhotoUrl;
        }
        if (fileInput) {
            fileInput.value = '';
        }
        if (imageInput) {
            imageInput.classList.remove('image-input-changed');
            imageInput.classList.add('image-input-empty');
        }
    };

    // Init add user modal
    var initAddUser = () => {
        const cancelButton = element.querySelector('[data-add-users-modal-action="cancel"]');
        const closeButton = element.querySelector('[data-add-users-modal-action="close"]');

        [cancelButton, closeButton].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    form.reset();
                    resetPhotoToDefault();
                    modal.hide();
                });
            }
        });
    };

    // Photo upload handling
    var initPhotoUpload = function () {
        const imageInputElement = document.querySelector('#kt_image_input_add');
        if (!imageInputElement) return;

        const fileInput = imageInputElement.querySelector('input[name="user_photo"]');
        const wrapper = imageInputElement.querySelector('.image-input-wrapper');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Please select a JPG or PNG image.');
                    this.value = '';
                    return;
                }

                // Validate file size (max 100KB)
                if (file.size > 100 * 1024) {
                    toastr.error('Image size must be less than 100KB.');
                    this.value = '';
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function (e) {
                    wrapper.style.backgroundImage = `url('${e.target.result}')`;
                    imageInputElement.classList.remove('image-input-empty');
                    imageInputElement.classList.add('image-input-changed');
                };
                reader.readAsDataURL(file);
            });
        }

        // Cancel button
        const cancelBtn = imageInputElement.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                resetPhotoToDefault();
            });
        }

        // Remove button
        const removeBtn = imageInputElement.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                resetPhotoToDefault();
            });
        }
    };

    // Form validation
    var initValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'user_name': {
                        validators: {
                            notEmpty: { message: 'Username is required' }
                        }
                    },
                    'user_email': {
                        validators: {
                            notEmpty: { message: 'Email is required' },
                            emailAddress: { message: 'Enter a valid email address' },
                        }
                    },
                    'user_mobile': {
                        validators: {
                            notEmpty: { message: 'Mobile no. is required' },
                            regexp: {
                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                message: 'Please enter a valid Bangladeshi mobile number'
                            },
                            stringLength: { min: 11, max: 11, message: 'The mobile number must be exactly 11 digits' }
                        }
                    },
                    'user_branch': {
                        validators: {
                            notEmpty: { message: 'Branch is required' }
                        }
                    },
                    'user_role': {
                        validators: {
                            notEmpty: { message: 'Role is required' }
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        const submitButton = element.querySelector('[data-add-users-modal-action="submit"]');
        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        fetch(storeUserRoute, {
                            method: "POST",
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(async response => {
                                const data = await response.json();
                                if (!response.ok) {
                                    throw { message: data.message || 'User creation failed', errors: data.errors };
                                }
                                return data;
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'User created successfully');
                                    modal.hide();
                                    form.reset();
                                    resetPhotoToDefault();
                                    KTUsersList.reload();
                                } else {
                                    toastr.error(data.message || 'User creation failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to create user');
                                console.error('Error:', error);
                            });
                    } else {
                        toastr.warning('Please fill all fields correctly');
                    }
                });
            });
        }

        // Role-based layout and validation logic
        const roleInputs = document.querySelectorAll('input[name="user_role"]');
        const branchDiv = document.getElementById('branch_input_div');
        const userNameDiv = document.getElementById('user_name_input_div');

        roleInputs.forEach(roleInput => {
            roleInput.addEventListener('change', function () {
                if (this.id === 'role_admin_input') {
                    if (branchDiv) branchDiv.style.display = 'none';
                    if (userNameDiv) {
                        userNameDiv.classList.remove('col-lg-6');
                        userNameDiv.classList.add('col-lg-12');
                    }
                    validator.disableValidator('user_branch', 'notEmpty');
                } else {
                    if (branchDiv) branchDiv.style.display = '';
                    if (userNameDiv) {
                        userNameDiv.classList.remove('col-lg-12');
                        userNameDiv.classList.add('col-lg-6');
                    }
                    validator.enableValidator('user_branch', 'notEmpty');
                }
            });
        });
    };

    return {
        init: function () {
            initAddUser();
            initPhotoUpload();
            initValidation();
        }
    };
}();

// ======================================== 
// Edit User Modal
// ========================================
var KTUsersEditUser = function () {
    const element = document.getElementById('kt_modal_edit_user');
    if (!element) return { init: function () { } };

    const form = element.querySelector('#kt_modal_edit_user_form');
    const modal = new bootstrap.Modal(element);
    const defaultPhotoUrl = "url('/img/male-placeholder.png')";

    let userId = null;
    let validator = null;
    let originalPhotoUrl = defaultPhotoUrl;

    // Toggle branch field visibility and validation
    const toggleBranchValidation = (role) => {
        const branchDiv = document.getElementById('branch_edit_div');
        const userNameDiv = document.getElementById('user_name_edit_div');

        if (role === 'admin') {
            if (branchDiv) branchDiv.style.display = 'none';
            if (userNameDiv) {
                userNameDiv.classList.remove('col-lg-6');
                userNameDiv.classList.add('col-lg-12');
            }
            if (validator) validator.disableValidator('user_branch_edit', 'notEmpty');
        } else {
            if (branchDiv) branchDiv.style.display = '';
            if (userNameDiv) {
                userNameDiv.classList.remove('col-lg-12');
                userNameDiv.classList.add('col-lg-6');
            }
            if (validator) validator.enableValidator('user_branch_edit', 'notEmpty');
        }
    };

    // Reset photo to original or default
    var resetPhotoToOriginal = function () {
        const wrapper = document.querySelector('#kt_image_input_edit .image-input-wrapper');
        const fileInput = document.querySelector('#kt_image_input_edit input[name="user_photo_edit"]');
        const imageInput = document.querySelector('#kt_image_input_edit');
        const removeInput = document.querySelector('#kt_image_input_edit input[name="remove_photo"]');

        if (wrapper) {
            wrapper.style.backgroundImage = originalPhotoUrl;
        }
        if (fileInput) {
            fileInput.value = '';
        }
        if (removeInput) {
            removeInput.value = '0';
        }
        if (imageInput) {
            imageInput.classList.remove('image-input-changed');
            if (originalPhotoUrl === defaultPhotoUrl) {
                imageInput.classList.add('image-input-empty');
            } else {
                imageInput.classList.remove('image-input-empty');
            }
        }
    };

    // Init Edit User Modal
    const initEditUser = () => {
        document.addEventListener('click', function (e) {
            const editBtn = e.target.closest("[data-bs-target='#kt_modal_edit_user']");
            if (!editBtn) return;

            e.preventDefault();
            userId = editBtn.getAttribute("data-user-id");

            if (!userId) return;
            if (form) form.reset();

            // AJAX data fetch
            fetch(`/settings/users/${userId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        const user = data.data;

                        const titleEl = document.getElementById("kt_modal_edit_user_title");
                        if (titleEl) titleEl.textContent = `Update user ${user.name}`;

                        document.querySelector("input[name='user_name_edit']").value = user.name;
                        document.querySelector("input[name='user_email_edit']").value = user.email;
                        document.querySelector("input[name='user_mobile_edit']").value = user.mobile_number;

                        const setSelect2Value = (name, value) => {
                            const el = $(`select[name="${name}"]`);
                            if (el.length) el.val(value).trigger('change');
                        };
                        setSelect2Value("user_branch_edit", user.branch_id);

                        const roleRadio = document.querySelector(`input[name='user_role_edit'][value="${user.role}"]`);
                        if (roleRadio) roleRadio.checked = true;

                        // Set photo
                        const wrapper = document.querySelector('#kt_image_input_edit .image-input-wrapper');
                        const imageInput = document.querySelector('#kt_image_input_edit');
                        if (user.photo_url) {
                            originalPhotoUrl = `url('${user.photo_url}')`;
                            wrapper.style.backgroundImage = originalPhotoUrl;
                            imageInput.classList.remove('image-input-empty');
                        } else {
                            originalPhotoUrl = defaultPhotoUrl;
                            wrapper.style.backgroundImage = defaultPhotoUrl;
                            imageInput.classList.add('image-input-empty');
                        }

                        toggleBranchValidation(user.role);
                        modal.show();

                        // Add role change listeners
                        const roleRadios = form.querySelectorAll('input[name="user_role_edit"]');
                        roleRadios.forEach((radio) => {
                            radio.addEventListener('change', function () {
                                toggleBranchValidation(this.value);
                            });
                        });
                    } else {
                        throw new Error(data.message || 'Invalid response data');
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    toastr.error(error.message || "Failed to load user details");
                });
        });

        // Cancel and close buttons
        const cancelButton = element.querySelector('[data-edit-users-modal-action="cancel"]');
        const closeButton = element.querySelector('[data-edit-users-modal-action="close"]');

        [cancelButton, closeButton].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    form.reset();
                    resetPhotoToOriginal();
                    modal.hide();
                });
            }
        });
    };

    // Photo upload handling for edit
    var initPhotoUpload = function () {
        const imageInputElement = document.querySelector('#kt_image_input_edit');
        if (!imageInputElement) return;

        const fileInput = imageInputElement.querySelector('input[name="user_photo_edit"]');
        const wrapper = imageInputElement.querySelector('.image-input-wrapper');
        const removeInput = imageInputElement.querySelector('input[name="remove_photo"]');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Please select a JPG or PNG image.');
                    this.value = '';
                    return;
                }

                if (file.size > 100 * 1024) {
                    toastr.error('Image size must be less than 100KB.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    wrapper.style.backgroundImage = `url('${e.target.result}')`;
                    imageInputElement.classList.remove('image-input-empty');
                    imageInputElement.classList.add('image-input-changed');
                    removeInput.value = '0';
                };
                reader.readAsDataURL(file);
            });
        }

        // Cancel button
        const cancelBtn = imageInputElement.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                wrapper.style.backgroundImage = originalPhotoUrl;
                fileInput.value = '';
                removeInput.value = '0';
                imageInputElement.classList.remove('image-input-changed');
            });
        }

        // Remove button
        const removeBtn = imageInputElement.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                wrapper.style.backgroundImage = defaultPhotoUrl;
                fileInput.value = '';
                removeInput.value = '1';
                imageInputElement.classList.remove('image-input-changed');
                imageInputElement.classList.add('image-input-empty');
            });
        }
    };

    // Form validation
    var initEditFormValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'user_name_edit': {
                        validators: {
                            notEmpty: { message: 'Username is required' }
                        }
                    },
                    'user_mobile_edit': {
                        validators: {
                            notEmpty: { message: 'Mobile no. is required' },
                            regexp: {
                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                message: 'Please enter a valid Bangladeshi mobile number'
                            },
                            stringLength: { min: 11, max: 11, message: 'The mobile number must be exactly 11 digits' }
                        }
                    },
                    'user_branch_edit': {
                        validators: {
                            notEmpty: { message: 'Branch is required' }
                        }
                    },
                    'user_role_edit': {
                        validators: {
                            notEmpty: { message: 'Role is required' }
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        const submitButton = element.querySelector('[data-edit-users-modal-action="submit"]');
        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('_method', 'PUT');

                        fetch(`/settings/users/${userId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(errorData => {
                                        throw new Error(errorData.message || 'Network response was not ok');
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'User updated successfully');
                                    modal.hide();
                                    KTUsersList.reload();
                                } else {
                                    throw new Error(data.message || 'User Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update user');
                                console.error('Error:', error);
                            });
                    } else {
                        toastr.warning('Please fill all required fields');
                    }
                });
            });
        }
    };

    return {
        init: function () {
            initEditUser();
            initPhotoUpload();
            initEditFormValidation();
        }
    };
}();

// ======================================== 
// Reset Password Modal
// ========================================
var KTUsersResetPassword = function () {
    const element = document.getElementById('kt_modal_edit_password');
    if (!element) return { init: function () { } };

    const form = element.querySelector('#kt_modal_edit_password_form');
    const modal = new bootstrap.Modal(element);
    let userId = null;
    let validator = null;

    var initEditPassword = () => {
        const passwordInput = document.getElementById('userPasswordNew');
        const strengthText = document.getElementById('password-strength-text');
        const strengthBar = document.getElementById('password-strength-bar');

        const cancelButton = element.querySelector('[data-kt-edit-password-modal-action="cancel"]');
        const closeButton = element.querySelector('[data-kt-edit-password-modal-action="close"]');

        [cancelButton, closeButton].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    form.reset();
                    modal.hide();
                    if (strengthText) strengthText.textContent = '';
                    if (strengthBar) {
                        strengthBar.className = 'progress-bar';
                        strengthBar.style.width = '0%';
                    }
                });
            }
        });

        // AJAX loading password modal data
        document.addEventListener('click', function (e) {
            // Handle password toggle
            const toggleBtn = e.target.closest('.toggle-password');
            if (toggleBtn) {
                const inputId = toggleBtn.getAttribute('data-target');
                const input = document.getElementById(inputId);
                const icon = toggleBtn.querySelector('i');

                if (input) {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    if (icon) {
                        icon.classList.toggle('ki-eye');
                        icon.classList.toggle('ki-eye-slash');
                    }
                }
                return;
            }

            // Handle edit password modal button
            const changePasswordBtn = e.target.closest('.change-password-btn');
            if (changePasswordBtn) {
                userId = changePasswordBtn.getAttribute('data-user-id');
                const userName = changePasswordBtn.getAttribute('data-user-name');
                const modalTitle = document.getElementById('kt_modal_edit_password_title');

                if (modalTitle) modalTitle.textContent = `Password Reset of ${userName}`;
            }
        });

        // Live strength meter
        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                const value = passwordInput.value;
                let score = 0;

                if (value.length >= 8) score++;
                if (/[A-Z]/.test(value)) score++;
                if (/[a-z]/.test(value)) score++;
                if (/\d/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;

                let strength = '';
                let barColor = '';
                let width = score * 20;

                switch (score) {
                    case 0:
                    case 1:
                        strength = 'Very Weak';
                        barColor = 'bg-danger';
                        break;
                    case 2:
                        strength = 'Weak';
                        barColor = 'bg-warning';
                        break;
                    case 3:
                        strength = 'Moderate';
                        barColor = 'bg-info';
                        break;
                    case 4:
                        strength = 'Strong';
                        barColor = 'bg-primary';
                        break;
                    case 5:
                        strength = 'Very Strong';
                        barColor = 'bg-success';
                        break;
                }

                strengthText.textContent = strength;
                strengthBar.className = `progress-bar ${barColor}`;
                strengthBar.style.width = `${width}%`;
            });
        }
    };

    var initFormValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'new_password': {
                        validators: {
                            notEmpty: { message: 'Password is required' },
                            stringLength: { min: 8, message: '* Must be at least 8 characters long' },
                            regexp: {
                                regexp: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/,
                                message: '* Must contain uppercase, lowercase, number, and special character'
                            }
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        const submitButton = element.querySelector('[data-kt-edit-password-modal-action="submit"]');
        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('_method', 'PUT');

                        fetch(`/settings/users/${userId}/password`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(errorData => {
                                        throw new Error(errorData.message || 'Network response was not ok');
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'Password updated successfully');
                                    modal.hide();
                                    form.reset();
                                } else {
                                    throw new Error(data.message || 'Password Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update password');
                                console.error('Error:', error);
                            });
                    } else {
                        toastr.warning('Please fill all required fields');
                    }
                });
            });
        }
    };

    return {
        init: function () {
            initEditPassword();
            initFormValidation();
        }
    };
}();

// ======================================== 
// Delete User Modal
// ========================================
var KTUsersDeleteUser = function () {
    const modalElement = document.getElementById('kt_modal_delete_user');
    if (!modalElement) return { init: function () { } };

    const modal = new bootstrap.Modal(modalElement);

    let currentUserId = null;
    let currentUserName = null;

    // Elements
    const stepWarning = document.getElementById('delete_step_warning');
    const stepConfirm = document.getElementById('delete_step_confirm');
    const userPhoto = document.getElementById('delete_user_photo');
    const userName = document.getElementById('delete_user_name');
    const userEmail = document.getElementById('delete_user_email');
    const userNameConfirm = document.getElementById('delete_user_name_confirm');
    const confirmInput = document.getElementById('delete_confirm_input');
    const btnProceed = document.getElementById('btn_delete_proceed');
    const btnBack = document.getElementById('btn_delete_back');
    const btnConfirm = document.getElementById('btn_delete_confirm');

    // Reset modal to initial state
    const resetModal = function () {
        if (stepWarning) stepWarning.style.display = 'block';
        if (stepConfirm) stepConfirm.style.display = 'none';
        if (confirmInput) {
            confirmInput.value = '';
            confirmInput.classList.remove('is-valid', 'is-invalid');
        }
        if (btnConfirm) btnConfirm.disabled = true;
    };

    // Handle delete button clicks (event delegation for dynamic content)
    const handleDeleteClick = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-user');
            if (!deleteBtn) return;

            e.preventDefault();
            e.stopPropagation();

            currentUserId = deleteBtn.getAttribute('data-user-id');
            currentUserName = deleteBtn.getAttribute('data-user-name');
            const email = deleteBtn.getAttribute('data-user-email');
            const photo = deleteBtn.getAttribute('data-user-photo');

            if (!currentUserId || currentUserId === 'null' || currentUserId === 'undefined') {
                toastr.error('Unable to identify user. Please refresh the page.');
                return;
            }

            // Populate modal
            if (userName) userName.textContent = currentUserName || 'Unknown User';
            if (userEmail) userEmail.textContent = email || '';
            if (userNameConfirm) userNameConfirm.textContent = currentUserName || 'Unknown User';
            if (userPhoto) userPhoto.src = photo || '/img/male-placeholder.png';

            resetModal();
            modal.show();
        });
    };

    // Handle proceed to confirmation
    const handleProceed = function () {
        if (!btnProceed) return;

        btnProceed.addEventListener('click', function () {
            if (stepWarning) stepWarning.style.display = 'none';
            if (stepConfirm) stepConfirm.style.display = 'block';
            if (confirmInput) confirmInput.focus();
        });
    };

    // Handle back button
    const handleBack = function () {
        if (!btnBack) return;

        btnBack.addEventListener('click', function () {
            if (stepWarning) stepWarning.style.display = 'block';
            if (stepConfirm) stepConfirm.style.display = 'none';
            if (confirmInput) {
                confirmInput.value = '';
                confirmInput.classList.remove('is-valid', 'is-invalid');
            }
            if (btnConfirm) btnConfirm.disabled = true;
        });
    };

    // Handle confirmation input
    const handleConfirmInput = function () {
        if (!confirmInput) return;

        confirmInput.addEventListener('input', function () {
            const value = this.value.trim();
            const isValid = value === 'DELETE';

            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', value.length > 0 && !isValid);

            if (btnConfirm) btnConfirm.disabled = !isValid;
        });

        // Handle Enter key
        confirmInput.addEventListener('keyup', function (e) {
            if (e.key === 'Enter' && this.value.trim() === 'DELETE') {
                if (btnConfirm) btnConfirm.click();
            }
        });
    };

    // Handle delete confirmation
    const handleDeleteConfirm = function () {
        if (!btnConfirm) return;

        btnConfirm.addEventListener('click', function () {
            if (!currentUserId || currentUserId === 'null' || currentUserId === 'undefined') {
                toastr.error('Unable to identify user. Please close this modal and try again.');
                return;
            }

            // Show loading
            btnConfirm.setAttribute('data-kt-indicator', 'on');
            btnConfirm.disabled = true;

            const deletedUserName = currentUserName;
            const url = routeDeleteUser.replace(':id', currentUserId);

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    btnConfirm.removeAttribute('data-kt-indicator');

                    if (data.success) {
                        modal.hide();

                        Swal.fire({
                            icon: 'success',
                            title: 'User Deleted',
                            html: `<p class="mb-2"><strong>${deletedUserName}</strong> has been deleted.</p>
                                   <p class="text-muted fs-7">The account has been deactivated and hidden. Data can be recovered by an administrator if needed.</p>`,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            KTUsersList.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Failed to delete user');
                    }
                })
                .catch(error => {
                    btnConfirm.removeAttribute('data-kt-indicator');
                    btnConfirm.disabled = false;
                    toastr.error(error.message || 'An error occurred while deleting the user');
                    console.error('Delete Error:', error);
                });
        });
    };

    // Handle modal hidden event
    const handleModalHidden = function () {
        modalElement.addEventListener('hidden.bs.modal', function () {
            resetModal();
            currentUserId = null;
            currentUserName = null;
        });
    };

    return {
        init: function () {
            handleDeleteClick();
            handleProceed();
            handleBack();
            handleConfirmInput();
            handleDeleteConfirm();
            handleModalHidden();
        }
    };
}();

// ======================================== 
// Recover User Modal
// ========================================
var KTUsersRecoverUser = function () {
    const modalElement = document.getElementById('kt_modal_recover_user');
    if (!modalElement) return { init: function () { } };

    const modal = new bootstrap.Modal(modalElement);

    let currentUserId = null;
    let currentUserName = null;

    const userNameEl = document.getElementById('recover_user_name');
    const btnConfirm = document.getElementById('btn_recover_confirm');

    // Handle recover button clicks (event delegation for dynamic content)
    const handleRecoverClick = function () {
        $(document).on('click', '.recover-user-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const recoverBtn = $(this);
            
            currentUserId = recoverBtn.attr('data-user-id');
            currentUserName = recoverBtn.attr('data-user-name');

            console.log('Recover button clicked - User ID:', currentUserId, 'Name:', currentUserName);

            if (!currentUserId || currentUserId === 'null' || currentUserId === 'undefined') {
                toastr.error('Unable to identify user. Please refresh the page.');
                return;
            }

            // Populate modal
            if (userNameEl) userNameEl.textContent = currentUserName || 'Unknown User';

            modal.show();
        });
    };

    // Handle recover confirmation
    const handleRecoverConfirm = function () {
        if (!btnConfirm) return;

        btnConfirm.addEventListener('click', function () {
            if (!currentUserId || currentUserId === 'null' || currentUserId === 'undefined') {
                toastr.error('Unable to identify user. Please close this modal and try again.');
                return;
            }

            // Show loading
            btnConfirm.setAttribute('data-kt-indicator', 'on');
            btnConfirm.disabled = true;

            fetch(routeRecoverUser, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_id: currentUserId
                })
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    btnConfirm.removeAttribute('data-kt-indicator');
                    btnConfirm.disabled = false;

                    if (data.success) {
                        modal.hide();
                        toastr.success(data.message || 'User recovered successfully');
                        KTUsersList.reload();
                    } else {
                        throw new Error(data.message || 'Failed to recover user');
                    }
                })
                .catch(error => {
                    btnConfirm.removeAttribute('data-kt-indicator');
                    btnConfirm.disabled = false;
                    toastr.error(error.message || 'An error occurred while recovering the user');
                    console.error('Recover Error:', error);
                });
        });
    };

    // Handle modal hidden event
    const handleModalHidden = function () {
        modalElement.addEventListener('hidden.bs.modal', function () {
            currentUserId = null;
            currentUserName = null;
        });
    };

    return {
        init: function () {
            handleRecoverClick();
            handleRecoverConfirm();
            handleModalHidden();
        }
    };
}();

// ======================================== 
// Initialize Tooltips
// ========================================
var KTTooltips = function () {
    return {
        init: function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                // Dispose existing tooltip to prevent duplicates
                var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (existingTooltip) {
                    existingTooltip.dispose();
                }
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    };
}();

// ======================================== 
// Metronic Standard Init
// ========================================
KTUtil.onDOMContentLoaded(function () {
    KTUsersList.init();
    KTUsersAddUser.init();
    KTUsersEditUser.init();
    KTUsersResetPassword.init();
    KTUsersDeleteUser.init();
    KTUsersRecoverUser.init();
    KTTooltips.init();
});
