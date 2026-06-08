"use strict";

// =========================================================================
// BidaEmployeeEdit
// Handles the edit-employee form with three permission tiers:
//   1. Admin            — may change pay scale + grade + basic salary
//   2. CPF Officer      — may change grade + basic salary (same active scale)
//   3. Others / locked  — personal details only (pay scale fields are read-only)
//
// Key fix: when the assigned pay scale is INACTIVE, canChangeGradeSalary
// starts as false (grade/salary selects are disabled). But when the Admin
// selects a NEW ACTIVE pay scale, we must unlock grade/salary and load them
// via AJAX.  The variable CAN_CHANGE_GRADE_SALARY_LIVE tracks this dynamic
// state and starts equal to CAN_CHANGE_GRADE_SALARY but is set to true
// after the Admin picks an active replacement scale.
// =========================================================================
var BidaEmployeeEdit = (function () {

      var form;
      var updateBtn;
      var validator;

      // Static permission flags read from PHP config on init.
      var CAN_CHANGE_PAY_SCALE = false;
      var CAN_CHANGE_GRADE_SALARY = false;

      // Dynamic flag — becomes true when Admin selects a valid active pay scale
      // even if the original assigned scale was inactive (canChangeGradeSalary=false).
      var CAN_CHANGE_GRADE_SALARY_LIVE = false;

      // ── FormValidation ────────────────────────────────────────────────────
      var initValidation = function () {
            var fields = {
                  cpf_account_no: {
                        validators: {
                              notEmpty: { message: "CPF account number is required" },
                              stringLength: { max: 50, message: "Maximum 50 characters" }
                        }
                  },
                  name: {
                        validators: {
                              notEmpty: { message: "Employee name is required" },
                              stringLength: { max: 255, message: "Maximum 255 characters" }
                        }
                  },
                  designation: {
                        validators: {
                              notEmpty: { message: "Designation is required" },
                              stringLength: { max: 255, message: "Maximum 255 characters" }
                        }
                  },
                  email: {
                        validators: {
                              emailAddress: { message: "Please enter a valid email" }
                        }
                  },
                  joining_date: {
                        validators: {
                              notEmpty: { message: "Joining date is required" }
                        }
                  },
                  status: {
                        validators: {
                              notEmpty: { message: "Status is required" }
                        }
                  }
            };

            // Only validate pay_scale_step_id when user can actually interact with it.
            // For the inactive-scale + Admin scenario the field becomes required once
            // a new active scale is chosen — we handle that dynamically via revalidate.
            if (CAN_CHANGE_GRADE_SALARY || CAN_CHANGE_PAY_SCALE) {
                  fields["pay_scale_step_id"] = {
                        validators: {
                              notEmpty: { message: "Please select a grade then a basic salary" }
                        }
                  };
            }

            validator = FormValidation.formValidation(form, {
                  fields: fields,
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: ".fv-row",
                              eleInvalidClass: "",
                              eleValidClass: ""
                        })
                  }
            });
      };

      // ── Build FormData (avoids phantom file issues) ───────────────────────
      var _buildFormData = function () {
            var fd = new FormData();

            // Laravel method spoofing
            fd.append("_method", "PUT");

            var scalarFields = [
                  "_token",
                  "cpf_account_no",
                  "name",
                  "designation",
                  "email",
                  "mobile_number",
                  "joining_date",
                  "retirement_date",
                  "pay_scale_id"
            ];

            scalarFields.forEach(function (name) {
                  var el = form.querySelector('[name="' + name + '"]');
                  if (el) {
                        fd.append(name, el.value || "");
                  }
            });

            // Status radio
            var checkedStatus = form.querySelector('[name="status"]:checked');
            if (checkedStatus) {
                  fd.append("status", checkedStatus.value);
            }

            // pay_scale_step_id — use the visible select when grade/salary is editable
            // (either originally OR after the Admin switched to an active scale),
            // otherwise fall back to the hidden field.
            if (CAN_CHANGE_GRADE_SALARY_LIVE) {
                  var salarySelect = document.getElementById("basic_salary_select");
                  if (salarySelect) {
                        fd.append("pay_scale_step_id", salarySelect.value || "");
                  }
            } else {
                  var hiddenStep = form.querySelector('input[name="pay_scale_step_id"][type="hidden"]');
                  if (hiddenStep) {
                        fd.append("pay_scale_step_id", hiddenStep.value || "");
                  }
            }

            // Photo — only append when a real file is selected
            var photoInput = document.getElementById("photo_file_input");
            if (photoInput && photoInput.files && photoInput.files.length > 0) {
                  var file = photoInput.files[0];
                  if (file.size > 0 && file.name !== "") {
                        fd.append("photo", file, file.name);
                  }
            }

            // Photo remove flag
            var photoRemoveInput = document.getElementById("photo_remove_input");
            if (photoRemoveInput) {
                  fd.append("photo_remove", photoRemoveInput.value);
            }

            return fd;
      };

      // ── AJAX submit ────────────────────────────────────────────────────────
      var handleSubmit = function () {
            updateBtn.addEventListener("click", function (e) {
                  e.preventDefault();

                  // Clear previous server errors
                  form.querySelectorAll(".server-error").forEach(function (el) { el.remove(); });

                  validator.validate().then(function (status) {
                        if (status !== "Valid") {
                              toastr.warning("Please fix the highlighted errors before saving.");
                              KTUtil.scrollTop();
                              return;
                        }

                        updateBtn.setAttribute("data-kt-indicator", "on");
                        updateBtn.disabled = true;

                        var formData = _buildFormData();

                        fetch(EmployeeEditConfig.updateUrl, {
                              method: "POST",      // POST + _method=PUT (Laravel method spoofing)
                              headers: {
                                    "X-CSRF-TOKEN": EmployeeEditConfig.csrfToken,
                                    "X-Requested-With": "XMLHttpRequest",
                                    "Accept": "application/json"
                                    // Do NOT set Content-Type — browser sets multipart boundary automatically
                              },
                              body: formData
                        })
                              .then(function (res) {
                                    if (res.ok) return res.json();

                                    if (res.status === 422) {
                                          return res.json().then(function (data) {
                                                throw { validation: true, errors: data.errors };
                                          });
                                    }

                                    return res.json()
                                          .then(function (data) {
                                                throw { validation: false, message: data.message || "Server error." };
                                          })
                                          .catch(function (thrown) {
                                                if (thrown && (thrown.validation !== undefined)) throw thrown;
                                                throw { validation: false, message: "Something went wrong. Please try again." };
                                          });
                              })
                              .then(function () {
                                    Swal.fire({
                                          text: "Employee updated successfully.",
                                          icon: "success",
                                          buttonsStyling: false,
                                          confirmButtonText: "OK",
                                          customClass: { confirmButton: "btn btn-primary" }
                                    }).then(function () {
                                          window.location.href = EmployeeEditConfig.showUrl;
                                    });
                              })
                              .catch(function (err) {
                                    if (err && err.validation && err.errors) {
                                          showServerErrors(err.errors);
                                    } else {
                                          showErrorBanner(
                                                (err && err.message) ? err.message : "Something went wrong. Please try again."
                                          );
                                    }
                              })
                              .finally(function () {
                                    updateBtn.removeAttribute("data-kt-indicator");
                                    updateBtn.disabled = false;
                              });
                  });
            });
      };

      // ── Flatpickr date pickers ─────────────────────────────────────────────
      var initDatePickers = function () {
            var sharedOpts = {
                  dateFormat: "Y-m-d",
                  altInput: true,
                  altFormat: "d M Y",
                  allowInput: true
            };

            var joiningInput = document.getElementById("joining_date_input");
            if (joiningInput) {
                  var existingJoining = joiningInput.value;
                  flatpickr(joiningInput, Object.assign({}, sharedOpts, {
                        defaultDate: existingJoining || null,
                        onChange: function (dates, str) {
                              joiningInput.value = str;
                              if (validator) validator.revalidateField("joining_date");
                        }
                  }));
            }

            var retirementInput = document.getElementById("retirement_date_input");
            if (retirementInput) {
                  var existingRetirement = retirementInput.value;
                  flatpickr(retirementInput, Object.assign({}, sharedOpts, {
                        defaultDate: existingRetirement || null
                  }));
            }
      };

      // =========================================================================
      // enableGradeSalarySelects
      // Physically removes the `disabled` attribute from the grade and salary
      // selects so they become interactive after the Admin picks an active scale.
      // =========================================================================
      var enableGradeSalarySelects = function () {
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");

            $grade.prop("disabled", false);
            $salary.prop("disabled", false);

            // Flip the live flag so _buildFormData picks up the visible selects
            CAN_CHANGE_GRADE_SALARY_LIVE = true;

            // Hide the locked / read-only notice and show the dynamic-unlock notice
            var lockedNotice = document.getElementById("notice_pay_scale_locked");
            var unlockedNotice = document.getElementById("notice_pay_scale_unlocked");
            if (lockedNotice) lockedNotice.classList.add("d-none");
            if (unlockedNotice) unlockedNotice.classList.remove("d-none");

            // Update the permission summary badge
            var permBadgeGrade = document.getElementById("perm_badge_grade");
            if (permBadgeGrade) {
                  permBadgeGrade.innerHTML =
                        '<i class="ki-outline ki-check fs-7 text-success me-1"></i>' +
                        'Change grade &amp; basic salary';
            }

            // Wire up grade change if not already wired
            _wireGradeChange();
      };

      // ── Wire grade → salary cascade ───────────────────────────────────────
      var _gradeChangeWired = false;
      var _wireGradeChange = function () {
            if (_gradeChangeWired) return;
            _gradeChangeWired = true;

            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");

            $grade.on("change", function () {
                  var grade = $(this).val();

                  // Resolve the active pay scale id
                  var payScaleId = _resolvePayScaleId();

                  if (grade) {
                        loadSteps(payScaleId, grade, null);
                  } else {
                        if ($salary.data("select2")) $salary.select2("destroy");
                        $salary.empty().append('<option value=""></option>');
                        $salary.prop("disabled", true);
                        $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                        var hint = document.getElementById("salary_hint");
                        if (hint) hint.textContent = "";
                  }
            });

            $(document).on("change", "#basic_salary_select", function () {
                  if (validator) validator.revalidateField("pay_scale_step_id");
            });
      };

      // Resolve the currently selected pay scale id from either the Select2
      // dropdown (Admin) or the hidden input (CPF Officer / read-only).
      var _resolvePayScaleId = function () {
            var $payScale = $("#pay_scale_select");
            if ($payScale.length && $payScale.val()) {
                  return $payScale.val();
            }
            var hiddenScale = form.querySelector('input[name="pay_scale_id"]');
            return hiddenScale ? hiddenScale.value : null;
      };

      // =========================================================================
      // AJAX: Load grades for a given pay scale
      // =========================================================================
      var loadGrades = function (payScaleId, preselectGrade, preselectStepId) {
            if (!payScaleId) return;

            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            // Destroy existing Select2 instances before manipulating options
            if ($grade.data("select2")) $grade.select2("destroy");
            if ($salary.data("select2")) $salary.select2("destroy");

            $grade.empty().append('<option value="">Loading grades…</option>');
            $grade.prop("disabled", true);

            $salary.empty().append('<option value=""></option>');
            $salary.prop("disabled", true);
            if (hint) hint.textContent = "";

            fetch(
                  EmployeeEditConfig.gradesByScaleUrl + "?pay_scale_id=" + encodeURIComponent(payScaleId),
                  { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } }
            )
                  .then(function (r) { return r.json(); })
                  .then(function (data) {
                        $grade.empty().append('<option value=""></option>');

                        if (data.grades && data.grades.length) {
                              data.grades.forEach(function (g) {
                                    var sel = (preselectGrade && parseInt(preselectGrade) === parseInt(g)) ? " selected" : "";
                                    $grade.append('<option value="' + g + '"' + sel + '>Grade ' + g + '</option>');
                              });
                              // Only enable grade select when we are in an editable state
                              if (CAN_CHANGE_GRADE_SALARY_LIVE) {
                                    $grade.prop("disabled", false);
                              }
                        } else {
                              if (hint) hint.textContent = "No grades found for this pay scale.";
                        }

                        $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });

                        // If a grade was pre-selected, auto-load its steps
                        if (
                              preselectGrade &&
                              data.grades &&
                              data.grades.indexOf(parseInt(preselectGrade)) !== -1
                        ) {
                              loadSteps(payScaleId, preselectGrade, preselectStepId);
                        } else {
                              $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                        }
                  })
                  .catch(function () {
                        $grade.empty().append('<option value=""></option>');
                        if (hint) hint.textContent = "Error loading grades.";
                        $grade.select2({ placeholder: "Error loading grades", minimumResultsForSearch: -1 });
                        $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                  });
      };

      // =========================================================================
      // AJAX: Load steps (basic salary) for a grade within a pay scale
      // =========================================================================
      var loadSteps = function (payScaleId, grade, preselectStepId) {
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            if ($salary.data("select2")) $salary.select2("destroy");

            $salary.empty().append('<option value="">Loading…</option>');
            $salary.prop("disabled", true);
            if (hint) hint.textContent = "";

            if (!grade) {
                  $salary.empty().append('<option value=""></option>');
                  $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                  return;
            }

            var url = EmployeeEditConfig.stepsUrl +
                  "?grade=" + encodeURIComponent(grade) +
                  (payScaleId ? "&pay_scale_id=" + encodeURIComponent(payScaleId) : "");

            fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } })
                  .then(function (r) { return r.json(); })
                  .then(function (data) {
                        $salary.empty().append('<option value=""></option>');

                        if (data.steps && data.steps.length) {
                              data.steps.forEach(function (s) {
                                    var sel = (preselectStepId && parseInt(preselectStepId) === parseInt(s.id)) ? " selected" : "";
                                    $salary.append(
                                          '<option value="' + s.id + '"' + sel + '>' +
                                          '৳ ' + Number(s.basic_salary).toLocaleString("en-IN") +
                                          ' (Step ' + s.step + ')' +
                                          '</option>'
                                    );
                              });
                              if (CAN_CHANGE_GRADE_SALARY_LIVE) {
                                    $salary.prop("disabled", false);
                              }
                              if (hint) hint.textContent = data.steps.length + " step(s) available in Grade " + grade;
                        } else {
                              if (hint) hint.textContent = "No steps found for Grade " + grade;
                        }

                        $salary.select2({ placeholder: "Select basic salary", minimumResultsForSearch: -1 });

                        if (validator) validator.revalidateField("pay_scale_step_id");
                  })
                  .catch(function () {
                        $salary.empty().append('<option value=""></option>');
                        if (hint) hint.textContent = "Error loading salary steps.";
                        $salary.select2({ placeholder: "Error loading", minimumResultsForSearch: -1 });
                  });
      };

      // =========================================================================
      // Wire up pay scale / grade / salary dropdowns
      // =========================================================================
      var initPayScaleDropdowns = function () {

            var $payScale = $("#pay_scale_select");
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");
            var cfg = EmployeeEditConfig;

            // ── Pay scale selector (Admin only) ────────────────────────────────
            if (CAN_CHANGE_PAY_SCALE && $payScale.length) {

                  // Initialise Select2 with disabled-option styling for inactive scales
                  $payScale.select2({
                        placeholder: "Select a pay scale",
                        templateResult: function (state) {
                              if (!state.id) return state.text;
                              var isActive = $(state.element).data("active") == "1";
                              var $el = $('<span></span>').text(state.text);
                              if (!isActive) {
                                    $el.addClass("text-muted");
                                    $el.append(' <span class="badge badge-light-danger ms-1 fs-9">Inactive</span>');
                              }
                              return $el;
                        },
                        templateSelection: function (state) {
                              return state.text || "Select a pay scale";
                        }
                  });

                  // Prevent selecting inactive pay scales
                  $payScale.on("select2:selecting", function (e) {
                        var selectedEl = e.params.args.data.element;
                        var isActive = $(selectedEl).data("active") == "1";

                        if (!isActive) {
                              e.preventDefault();
                              Swal.fire({
                                    icon: "warning",
                                    title: "Inactive Pay Scale",
                                    text: "Only an active pay scale can be assigned to an employee.",
                                    buttonsStyling: false,
                                    confirmButtonText: "OK",
                                    customClass: { confirmButton: "btn btn-warning" }
                              });
                        }
                  });

                  // ── Pay scale change ────────────────────────────────────────────
                  // This is the key fix: when an Admin selects a NEW ACTIVE pay scale
                  // (regardless of the initial canChangeGradeSalary state), we must:
                  //   1. Unlock the grade and salary selects (enableGradeSalarySelects).
                  //   2. Load grades for the newly selected scale via AJAX.
                  $payScale.on("change", function () {
                        var scaleId = $(this).val();
                        var $selected = $(this).find("option:selected");
                        var isActive = $selected.data("active") == "1";
                        var hint = document.getElementById("pay_scale_hint");

                        if (!scaleId) {
                              if (hint) hint.textContent = "";
                              // If going back to blank and grade/salary was unlocked via
                              // a previous selection, lock them again.
                              if (!CAN_CHANGE_GRADE_SALARY) {
                                    _lockGradeSalarySelects();
                              }
                              return;
                        }

                        if (!isActive) {
                              if (hint) {
                                    hint.textContent = "This pay scale is inactive and cannot be assigned.";
                                    hint.className = "form-text text-danger";
                              }
                              // Lock grade/salary if they were dynamically unlocked
                              if (!CAN_CHANGE_GRADE_SALARY) {
                                    _lockGradeSalarySelects();
                              }
                              return;
                        }

                        // Active pay scale selected ─────────────────────────────────
                        if (hint) {
                              hint.textContent = "";
                              hint.className = "form-text";
                        }

                        // Unlock grade/salary if they were locked due to inactive assigned scale
                        if (!CAN_CHANGE_GRADE_SALARY_LIVE) {
                              enableGradeSalarySelects();
                        }

                        // Load grades for the newly selected pay scale (no preselection)
                        loadGrades(scaleId, null, null);
                  });
            }

            // ── Grade selector (Admin + CPF Officer with active scale) ─────────
            if (CAN_CHANGE_GRADE_SALARY && $grade.length) {

                  $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });

                  // Wire grade change immediately (normal flow)
                  CAN_CHANGE_GRADE_SALARY_LIVE = true;
                  _wireGradeChange();

                  // ── Auto-load on page load ──────────────────────────────────────
                  var initScaleId = cfg.employee.pay_scale_id;
                  var initGrade = cfg.employee.grade;
                  var initStepId = cfg.employee.pay_scale_step_id;

                  if (initScaleId && initGrade) {
                        // Admin with active scale: reload grades list + steps
                        if (CAN_CHANGE_PAY_SCALE) {
                              loadGrades(initScaleId, initGrade, initStepId);
                        } else {
                              // CPF Officer: grades are already rendered server-side; just load steps
                              loadSteps(initScaleId, initGrade, initStepId);
                        }
                  } else {
                        $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                  }

            } else {
                  // Read-only OR inactive-scale Admin state:
                  // Just initialise Select2 visually (disabled) so the UI is consistent.
                  if ($grade.length) {
                        $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });
                  }
                  if ($salary.length) {
                        $salary.select2({ placeholder: "Select basic salary", minimumResultsForSearch: -1 });
                  }

                  // If Admin can change the pay scale (e.g. assigned scale is inactive),
                  // wire the grade change handler early so it's ready once the scale
                  // is selected and selects are unlocked via enableGradeSalarySelects().
                  // (The actual wiring is deferred inside enableGradeSalarySelects.)
            }
      };

      // Lock grade/salary selects back to disabled state (e.g. when Admin
      // clears the pay scale selection or picks an inactive one again).
      var _lockGradeSalarySelects = function () {
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");

            CAN_CHANGE_GRADE_SALARY_LIVE = false;

            if ($grade.data("select2")) $grade.select2("destroy");
            if ($salary.data("select2")) $salary.select2("destroy");

            $grade.empty().append('<option value=""></option>');
            $grade.prop("disabled", true);
            $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });

            $salary.empty().append('<option value=""></option>');
            $salary.prop("disabled", true);
            $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });

            var hint = document.getElementById("salary_hint");
            if (hint) hint.textContent = "";

            // Re-show the locked notice
            var lockedNotice = document.getElementById("notice_pay_scale_locked");
            var unlockedNotice = document.getElementById("notice_pay_scale_unlocked");
            if (lockedNotice) lockedNotice.classList.remove("d-none");
            if (unlockedNotice) unlockedNotice.classList.add("d-none");
      };

      // ── KTImageInput remove action — flag for server ───────────────────────
      var initPhotoRemove = function () {
            var photoInputEl = document.getElementById("kt_employee_photo_input");
            if (!photoInputEl) return;

            var removeBtn = photoInputEl.querySelector('[data-kt-image-input-action="remove"]');
            if (removeBtn) {
                  removeBtn.addEventListener("click", function () {
                        var flag = document.getElementById("photo_remove_input");
                        if (flag) flag.value = "1";
                  });
            }

            var fileInput = document.getElementById("photo_file_input");
            if (fileInput) {
                  fileInput.addEventListener("change", function () {
                        var flag = document.getElementById("photo_remove_input");
                        if (flag) flag.value = "0";
                  });
            }
      };

      // ── Server-side 422 error display ─────────────────────────────────────
      var showServerErrors = function (errors) {
            form.querySelectorAll(".server-error").forEach(function (el) { el.remove(); });

            Object.keys(errors).forEach(function (field) {
                  var input = form.querySelector('[name="' + field + '"]');
                  if (!input) return;

                  var row = input.closest(".fv-row");
                  if (row) {
                        var msg = document.createElement("div");
                        msg.className = "fv-plugins-message-container server-error mt-2";
                        msg.innerHTML =
                              '<div class="fv-help-block"><span role="alert">' +
                              errors[field][0] +
                              "</span></div>";
                        row.appendChild(msg);
                  }
            });

            KTUtil.scrollTop();
      };

      var showErrorBanner = function (message) {
            var c = document.getElementById("error-container");
            if (!c) return;
            c.innerHTML =
                  '<div class="alert alert-danger d-flex align-items-center p-5 mb-5">' +
                  '<i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>' +
                  '<div class="d-flex flex-column"><span class="fw-bold fs-5">' +
                  message +
                  '</span></div>' +
                  '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>' +
                  '</div>';
      };

      // ── Public API ────────────────────────────────────────────────────────
      return {
            init: function () {
                  form = document.getElementById("kt_edit_employee_form");
                  updateBtn = document.getElementById("btn_update");

                  if (!form || !updateBtn) {
                        console.error("BidaEmployeeEdit: required DOM elements not found.");
                        return;
                  }

                  // Read permission flags from server config
                  CAN_CHANGE_PAY_SCALE = EmployeeEditConfig.canChangePayScale === true;
                  CAN_CHANGE_GRADE_SALARY = EmployeeEditConfig.canChangeGradeSalary === true;
                  // Live flag starts equal to static flag
                  CAN_CHANGE_GRADE_SALARY_LIVE = CAN_CHANGE_GRADE_SALARY;

                  initValidation();
                  handleSubmit();
                  initDatePickers();
                  initPayScaleDropdowns();
                  initPhotoRemove();
            }
      };

})();

KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeEdit.init();
});
