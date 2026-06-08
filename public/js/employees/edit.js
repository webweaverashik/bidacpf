"use strict";

// =========================================================================
// BidaEmployeeEdit
// =========================================================================
var BidaEmployeeEdit = (function () {
      var form;
      var updateBtn;
      var validator;

      // ── FormValidation ────────────────────────────────────────────────────
      var initValidation = function () {
            validator = FormValidation.formValidation(form, {
                  fields: {
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
                        pay_scale_step_id: {
                              validators: {
                                    notEmpty: { message: "Please select a grade then a basic salary" }
                              }
                        },
                        status: {
                              validators: {
                                    notEmpty: { message: "Status is required" }
                              }
                        }
                  },
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

      // ── Build FormData (safe — avoids phantom file issue) ─────────────────
      var _buildFormData = function () {
            var fd = new FormData();

            // Method spoofing for Laravel PUT
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
                  "pay_scale_step_id",
                  "pay_scale_id",
                  "status"
            ];

            scalarFields.forEach(function (name) {
                  var el = form.querySelector('[name="' + name + '"]');
                  if (el) {
                        fd.append(name, el.value || "");
                  }
            });

            // Status radio — ensure the checked value is used
            var checkedStatus = form.querySelector('[name="status"]:checked');
            if (checkedStatus) {
                  fd.set("status", checkedStatus.value);
            }

            // Photo — only append if a real file is selected (size > 0)
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

                  validator.validate().then(function (status) {
                        if (status !== "Valid") {
                              KTUtil.scrollTop();
                              return;
                        }

                        updateBtn.setAttribute("data-kt-indicator", "on");
                        updateBtn.disabled = true;

                        var formData = _buildFormData();

                        fetch(EmployeeEditConfig.updateUrl, {
                              method: "POST",   // POST + _method=PUT (Laravel method spoofing)
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
                                          .catch(function () {
                                                throw { validation: false, message: "Something went wrong. Please try again." };
                                          });
                              })
                              .then(function (data) {
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

      // ── Grade → Basic Salary AJAX loader ──────────────────────────────────
      var initGradeChange = function () {
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            if (!$grade.length) return;

            // Helper: load steps for a given grade
            var loadSteps = function (grade, preselect) {
                  if ($salary.data("select2")) {
                        $salary.select2("destroy");
                  }
                  $salary.empty().append('<option value="">Loading…</option>');
                  $salary.prop("disabled", true);
                  if (hint) hint.textContent = "";

                  if (!grade) {
                        $salary.empty().append('<option value=""></option>');
                        $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
                        return;
                  }

                  fetch(
                        EmployeeEditConfig.stepsUrl + "?grade=" + encodeURIComponent(grade),
                        { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } }
                  )
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                              $salary.empty().append('<option value=""></option>');

                              if (data.steps && data.steps.length) {
                                    data.steps.forEach(function (s) {
                                          var selected = (preselect && parseInt(preselect) === parseInt(s.id)) ? ' selected' : '';
                                          $salary.append(
                                                '<option value="' + s.id + '"' + selected + '>' +
                                                '৳ ' + Number(s.basic_salary).toLocaleString("en-IN") +
                                                '</option>'
                                          );
                                    });
                                    $salary.prop("disabled", false);
                                    if (hint) hint.textContent = data.steps.length + " steps available in Grade " + grade;
                              } else {
                                    if (hint) hint.textContent = "No steps found for Grade " + grade;
                              }

                              $salary.select2({ placeholder: "Select basic salary", minimumResultsForSearch: -1 });
                        })
                        .catch(function () {
                              $salary.empty().append('<option value=""></option>');
                              if (hint) hint.textContent = "Error loading salary steps.";
                              $salary.select2({ placeholder: "Error loading", minimumResultsForSearch: -1 });
                        });
            };

            // Initialise Select2 on grade dropdown
            $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });

            // On grade change load steps (no preselect needed — user is choosing a new one)
            $grade.on("change", function () {
                  loadSteps($(this).val(), null);
            });

            // On salary change re-validate the hidden field
            $(document).on("change", "#basic_salary_select", function () {
                  if (validator) validator.revalidateField("pay_scale_step_id");
            });

            // ── Auto-load current employee's grade & step on page load ────────
            var currentGrade = EmployeeEditConfig.employee.grade;
            var currentStepId = EmployeeEditConfig.employee.pay_scale_step_id;

            if (currentGrade) {
                  loadSteps(currentGrade, currentStepId);
            } else {
                  $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
            }
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

                  initValidation();
                  handleSubmit();
                  initDatePickers();
                  initGradeChange();
                  initPhotoRemove();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeEdit.init();
});