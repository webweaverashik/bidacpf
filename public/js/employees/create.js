"use strict";

// =========================================================================
// BidaEmployeeCreate
// =========================================================================
var BidaEmployeeCreate = (function () {

      var stepper;
      var form;
      var submitBtn;
      var nextBtn;
      var prevBtn;
      var stepperObj;
      var validations = [];

      // ── Stepper initialisation ───────────────────────────────────────
      var initStepper = function () {
            stepperObj = new KTStepper(stepper);

            stepperObj.on("kt.stepper.changed", function (s) {
                  var current = s.getCurrentStepIndex();
                  _syncUI(current);
            });

            stepperObj.on("kt.stepper.next", function (s) {
                  var currentStep = s.getCurrentStepIndex();
                  var validator = validations[currentStep - 1];

                  var proceed = function () {
                        // Extra guard for Step 1: the basic-salary <select> is
                        // disabled until a grade is chosen, and FormValidation
                        // skips disabled fields — so notEmpty never fires on it.
                        if (currentStep === 1) {
                              var salary = form.querySelector('[name="pay_scale_step_id"]');
                              if (!salary || !salary.value) {
                                    _markSalaryError(true);
                                    _warn("Please select a grade and basic salary before continuing.");
                                    KTUtil.scrollTop();
                                    return;
                              }
                              _markSalaryError(false);

                              // Multi-scale guard: pay scale must be selected first.
                              if (EmployeeConfig.multiPayScale) {
                                    var payScaleEl = form.querySelector('[name="pay_scale_id"]');
                                    if (!payScaleEl || !payScaleEl.value) {
                                          _markPayScaleError(true);
                                          _warn("Please select a pay scale before continuing.");
                                          KTUtil.scrollTop();
                                          return;
                                    }
                                    _markPayScaleError(false);
                              }
                        }

                        s.goNext();
                        KTUtil.scrollTop();
                  };

                  if (validator) {
                        validator.validate().then(function (status) {
                              if (status === "Valid") {
                                    proceed();
                              } else {
                                    _warn("Please fill in all required fields before continuing.");
                                    KTUtil.scrollTop();
                              }
                        });
                  } else {
                        proceed();
                  }
            });

            stepperObj.on("kt.stepper.previous", function (s) {
                  s.goPrevious();
                  KTUtil.scrollTop();
            });

            _syncUI(1);
      };

      // ── Centralised UI sync ──────────────────────────────────────────
      var _syncUI = function (current) {
            document.querySelectorAll('[data-kt-stepper-element="content"]')
                  .forEach(function (panel, idx) {
                        if (idx === current - 1) {
                              panel.classList.remove("d-none");
                              panel.classList.add("current");
                        } else {
                              panel.classList.add("d-none");
                              panel.classList.remove("current");
                        }
                  });

            if (current === 1) {
                  _show(nextBtn); _hide(submitBtn); _hide(prevBtn);
            } else if (current === 2) {
                  _hide(nextBtn); _show(submitBtn); _show(prevBtn);
            } else {
                  _hide(nextBtn); _hide(submitBtn); _hide(prevBtn);
            }
      };

      var _show = function (el) {
            if (el) { el.classList.remove("d-none"); el.style.display = ""; }
      };

      var _hide = function (el) {
            if (el) { el.classList.add("d-none"); el.style.display = "none"; }
      };

      // ── Toastr warning helper ────────────────────────────────────────
      var _warn = function (message) {
            if (typeof toastr !== "undefined" && toastr && typeof toastr.warning === "function") {
                  toastr.warning(message);
            }
      };

      // ── Inline error for the (disabled) basic-salary select ──────────
      var _markSalaryError = function (show) {
            var salary = form.querySelector('[name="pay_scale_step_id"]');
            if (!salary) return;
            var row = salary.closest(".fv-row");
            if (!row) return;
            var existing = row.querySelector(".salary-guard-error");
            if (show) {
                  if (!existing) {
                        var msg = document.createElement("div");
                        msg.className = "fv-plugins-message-container salary-guard-error mt-2";
                        msg.innerHTML =
                              '<div class="fv-help-block"><span role="alert">' +
                              'Please select a grade and basic salary' +
                              '</span></div>';
                        row.appendChild(msg);
                  }
            } else if (existing) {
                  existing.remove();
            }
      };

      // ── Inline error for the pay-scale select (multi-scale only) ─────
      var _markPayScaleError = function (show) {
            var psEl = form.querySelector('[name="pay_scale_id"]');
            if (!psEl) return;
            var row = psEl.closest(".fv-row");
            if (!row) return;
            var existing = row.querySelector(".pay-scale-guard-error");
            if (show) {
                  if (!existing) {
                        var msg = document.createElement("div");
                        msg.className = "fv-plugins-message-container pay-scale-guard-error mt-2";
                        msg.innerHTML =
                              '<div class="fv-help-block"><span role="alert">' +
                              'Please select a pay scale' +
                              '</span></div>';
                        row.appendChild(msg);
                  }
            } else if (existing) {
                  existing.remove();
            }
      };

      // ── Block spaces in the CPF Account No field ──────────────────────
      var initCpfAccountNoFilter = function () {
            var input = form.querySelector('[name="cpf_account_no"]');
            if (!input) return;

            input.addEventListener("keydown", function (e) {
                  if (e.key === " " || e.code === "Space" || e.keyCode === 32) {
                        e.preventDefault();
                  }
            });

            input.addEventListener("input", function () {
                  var cleaned = input.value.replace(/\s+/g, "");
                  if (cleaned !== input.value) {
                        var pos = input.selectionStart - (input.value.length - cleaned.length);
                        input.value = cleaned;
                        if (pos >= 0) input.setSelectionRange(pos, pos);
                  }
                  if (validations[0]) validations[0].revalidateField("cpf_account_no");
            });
      };

      // ── FormValidation ───────────────────────────────────────────────
      var initValidation = function () {

            // Step 1 — build the fields object dynamically.
            // Include pay_scale_id validation ONLY when the selector is rendered
            // (multiPayScale === true). For single-scale setups it's a hidden
            // input that always has a value, so no validation needed.
            var step1Fields = {
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
            };

            // Add pay_scale_id field validation only in multi-scale mode.
            if (EmployeeConfig.multiPayScale) {
                  step1Fields.pay_scale_id = {
                        validators: {
                              notEmpty: { message: "Please select a pay scale" }
                        }
                  };
            }

            validations.push(
                  FormValidation.formValidation(form, {
                        fields: step1Fields,
                        plugins: {
                              trigger: new FormValidation.plugins.Trigger(),
                              bootstrap: new FormValidation.plugins.Bootstrap5({
                                    rowSelector: ".fv-row",
                                    eleInvalidClass: "",
                                    eleValidClass: ""
                              })
                        }
                  })
            );

            // Step 2
            validations.push(
                  FormValidation.formValidation(form, {
                        fields: {
                              opening_employee_contribution: {
                                    validators: {
                                          notEmpty: { message: "Employee contribution is required" },
                                          integer: { message: "Must be a whole number" },
                                          greaterThan: { min: 0, inclusive: true, message: "Cannot be negative" }
                                    }
                              },
                              opening_government_contribution: {
                                    validators: {
                                          notEmpty: { message: "Government contribution is required" },
                                          integer: { message: "Must be a whole number" },
                                          greaterThan: { min: 0, inclusive: true, message: "Cannot be negative" }
                                    }
                              },
                              opening_bank_interest: {
                                    validators: {
                                          notEmpty: { message: "Bank interest is required" },
                                          integer: { message: "Must be a whole number" },
                                          greaterThan: { min: 0, inclusive: true, message: "Cannot be negative" }
                                    }
                              },
                              opening_effective_date: {
                                    validators: {
                                          notEmpty: { message: "Effective date is required" }
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
                  })
            );
      };

      // ── Build FormData manually ───────────────────────────────────────
      var _buildFormData = function () {
            var fd = new FormData();

            var scalarFields = [
                  "_token", "cpf_account_no", "name", "designation",
                  "email", "mobile_number", "joining_date", "retirement_date",
                  "pay_scale_step_id", "pay_scale_id",
                  "opening_employee_contribution", "opening_government_contribution",
                  "opening_bank_interest",
                  "opening_effective_date"
            ];

            scalarFields.forEach(function (name) {
                  var el = form.querySelector('[name="' + name + '"]');
                  if (el) fd.append(name, el.value || "");
            });

            // Photo — only real files (size > 0).
            var photoInput = document.getElementById("photo_file_input");
            if (photoInput && photoInput.files && photoInput.files.length > 0) {
                  var file = photoInput.files[0];
                  if (file.size > 0 && file.name !== "") {
                        fd.append("photo", file, file.name);
                  }
            }

            return fd;
      };

      // ── AJAX submit ──────────────────────────────────────────────────
      var handleSubmit = function () {
            submitBtn.addEventListener("click", function (e) {
                  e.preventDefault();

                  var validator = validations[1];
                  validator.validate().then(function (status) {
                        if (status !== "Valid") {
                              _warn("Please correct the highlighted fields before submitting.");
                              KTUtil.scrollTop();
                              return;
                        }

                        submitBtn.setAttribute("data-kt-indicator", "on");
                        submitBtn.disabled = true;

                        var formData = _buildFormData();

                        fetch(EmployeeConfig.storeUrl, {
                              method: "POST",
                              headers: {
                                    "X-CSRF-TOKEN": EmployeeConfig.csrfToken,
                                    "X-Requested-With": "XMLHttpRequest",
                                    "Accept": "application/json"
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
                                    var nameEl = document.getElementById("created_employee_name");
                                    var acctEl = document.getElementById("created_cpf_account");
                                    if (nameEl) nameEl.textContent = data.employee ? data.employee.name : "";
                                    if (acctEl) acctEl.textContent = data.employee ? data.employee.cpf_account_no : "";

                                    var viewBtn = document.getElementById("btn_view_employee");
                                    if (viewBtn && data.employee && data.employee.id) {
                                          viewBtn.href = EmployeeConfig.showUrl.replace(":id", data.employee.id);
                                    }

                                    stepperObj.goNext();
                              })
                              .catch(function (err) {
                                    if (err && err.validation && err.errors) {
                                          showServerErrors(err.errors);
                                    } else {
                                          showErrorBanner((err && err.message) ? err.message : "Something went wrong. Please try again.");
                                    }
                              })
                              .finally(function () {
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                              });
                  });
            });
      };

      // ── Flatpickr date pickers ───────────────────────────────────────
      var initDatePickers = function () {
            var sharedOpts = {
                  dateFormat: "Y-m-d",
                  altInput: true,
                  altFormat: "d M Y",
                  allowInput: true
            };

            flatpickr("#joining_date_input", Object.assign({}, sharedOpts, {
                  onChange: function (dates, str) {
                        var orig = form.querySelector('[name="joining_date"]');
                        if (orig) orig.value = str;
                        if (validations[0]) validations[0].revalidateField("joining_date");
                  }
            }));

            flatpickr("#retirement_date_input", Object.assign({}, sharedOpts));

            flatpickr("#opening_effective_date_input", Object.assign({}, sharedOpts, {
                  onChange: function (dates, str) {
                        var orig = form.querySelector('[name="opening_effective_date"]');
                        if (orig) orig.value = str;
                        if (validations[1]) validations[1].revalidateField("opening_effective_date");
                  }
            }));
      };

      // ── Helpers: load grades for a pay scale via AJAX ─────────────────
      var _loadGrades = function (payScaleId, onDone) {
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            // Reset downstream selects.
            _resetGrade($grade);
            _resetSalary($salary, hint);

            if (!payScaleId) return;

            $grade.prop("disabled", true);
            $grade.empty().append('<option value="">Loading…</option>');

            fetch(
                  EmployeeConfig.gradesUrl + "?pay_scale_id=" + encodeURIComponent(payScaleId),
                  { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } }
            )
                  .then(function (r) { return r.json(); })
                  .then(function (data) {
                        $grade.empty().append('<option value=""></option>');
                        if (data.grades && data.grades.length) {
                              data.grades.forEach(function (g) {
                                    $grade.append('<option value="' + g + '">Grade ' + g + '</option>');
                              });
                              $grade.prop("disabled", false);
                        }
                        $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });
                        if (typeof onDone === "function") onDone();
                  })
                  .catch(function () {
                        $grade.empty().append('<option value=""></option>');
                        $grade.select2({ placeholder: "Error loading grades", minimumResultsForSearch: -1 });
                  });
      };

      // ── Helpers: load steps (basic salary) for a grade via AJAX ───────
      var _loadSteps = function (grade, payScaleId) {
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            _resetSalary($salary, hint);

            if (!grade) return;

            $salary.empty().append('<option value="">Loading…</option>');
            $salary.prop("disabled", true);

            var url = EmployeeConfig.stepsUrl +
                  "?grade=" + encodeURIComponent(grade) +
                  (payScaleId ? "&pay_scale_id=" + encodeURIComponent(payScaleId) : "");

            fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } })
                  .then(function (r) { return r.json(); })
                  .then(function (data) {
                        $salary.empty().append('<option value=""></option>');
                        if (data.steps && data.steps.length) {
                              data.steps.forEach(function (s) {
                                    $salary.append(
                                          '<option value="' + s.id + '">' +
                                          '\u09F3\u00A0' + Number(s.basic_salary).toLocaleString("en-IN") +
                                          ' (Step ' + s.step + ')' +
                                          '</option>'
                                    );
                              });
                              $salary.prop("disabled", false);
                              if (hint) hint.textContent = data.steps.length + " steps in Grade " + grade;
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

      var _resetGrade = function ($grade) {
            if ($grade.data("select2")) $grade.select2("destroy");
            $grade.empty().append('<option value=""></option>');
            $grade.prop("disabled", true);
            $grade.select2({ placeholder: "Select a grade", minimumResultsForSearch: -1 });
      };

      var _resetSalary = function ($salary, hint) {
            if ($salary.data("select2")) $salary.select2("destroy");
            $salary.empty().append('<option value=""></option>');
            $salary.prop("disabled", true);
            $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });
            if (hint) hint.textContent = "";
      };

      // ── Pay Scale → Grade → Step cascade wiring ───────────────────────
      var initGradeChange = function () {
            var $grade = $("#grade_select");
            var $salary = $("#basic_salary_select");
            var hint = document.getElementById("salary_hint");

            if (!$grade.length) return;

            // Initialise Select2 on salary.
            $salary.select2({ placeholder: "Select grade first", minimumResultsForSearch: -1 });

            // ── CASE A: Multiple pay scales ───────────────────────────────
            // Pay Scale → reload grades → reset steps.
            // Grade     → reload steps for the CHOSEN pay scale.
            if (EmployeeConfig.multiPayScale) {

                  var $payScale = $("#pay_scale_select");

                  // Initialise Select2 on pay scale dropdown.
                  $payScale.select2({ placeholder: "Select a pay scale" });

                  $payScale.on("change", function () {
                        var psId = $(this).val();

                        // Clear the pay-scale guard error once a scale is picked.
                        _markPayScaleError(false);
                        if (validations[0]) validations[0].revalidateField("pay_scale_id");

                        // Load grades for the selected pay scale.
                        _loadGrades(psId);
                  });

                  $grade.on("change", function () {
                        var grade = $(this).val();
                        var psId = $("#pay_scale_select").val();

                        _markSalaryError(false);
                        _loadSteps(grade, psId);
                  });

            } else {
                  // ── CASE B: Single pay scale (legacy — no pay-scale selector) ──
                  // Grade → load steps using the default pay scale id.
                  $grade.on("change", function () {
                        var grade = $(this).val();

                        _markSalaryError(false);
                        if ($salary.data("select2")) $salary.select2("destroy");

                        _resetSalary($salary, hint);

                        if (!grade) return;

                        _loadSteps(grade, EmployeeConfig.defaultPayScaleId);
                  });
            }

            // Whenever a salary step is chosen, clear guard errors + revalidate.
            $(document).on("change", "#basic_salary_select", function () {
                  _markSalaryError(false);
                  if (validations[0]) validations[0].revalidateField("pay_scale_step_id");
            });
      };

      // ── Live net opening balance computation ─────────────────────────
      var initOpeningBalanceCompute = function () {
            var display = document.getElementById("net_opening_balance_display");
            if (!display) return;

            var compute = function () {
                  var own = parseInt(form.querySelector('[name="opening_employee_contribution"]').value) || 0;
                  var govt = parseInt(form.querySelector('[name="opening_government_contribution"]').value) || 0;
                  var interest = parseInt(form.querySelector('[name="opening_bank_interest"]').value) || 0;
                  var net = own + govt + interest;

                  display.textContent = "\u09F3\u00A0" + net.toLocaleString("en-IN");

                  var box = display.parentElement;
                  ["bg-light-success", "bg-light-danger", "bg-light-warning"].forEach(function (c) { box.classList.remove(c); });
                  ["text-success", "text-danger", "text-warning"].forEach(function (c) { display.classList.remove(c); });

                  if (net > 0) {
                        box.classList.add("bg-light-success"); display.classList.add("text-success");
                  } else if (net < 0) {
                        box.classList.add("bg-light-danger"); display.classList.add("text-danger");
                  } else {
                        box.classList.add("bg-light-warning"); display.classList.add("text-warning");
                  }
            };

            ["opening_employee_contribution", "opening_government_contribution",
                  "opening_bank_interest"
            ].forEach(function (name) {
                  var el = form.querySelector('[name="' + name + '"]');
                  if (el) el.addEventListener("input", compute);
            });

            compute();
      };

      // ── Server-side 422 error display ───────────────────────────────
      var showServerErrors = function (errors) {
            form.querySelectorAll(".server-error").forEach(function (el) { el.remove(); });

            var step1Keys = [
                  "cpf_account_no", "name", "designation", "email",
                  "mobile_number", "joining_date", "retirement_date",
                  "pay_scale_step_id", "pay_scale_id", "photo"
            ];
            var goStep1 = false;

            Object.keys(errors).forEach(function (field) {
                  if (step1Keys.indexOf(field) !== -1) goStep1 = true;

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

            if (goStep1) stepperObj.goTo(1);
            _warn("The server rejected some fields. Please review and try again.");
            KTUtil.scrollTop();
      };

      var showErrorBanner = function (message) {
            var c = document.getElementById("error-container");
            if (!c) return;
            c.innerHTML =
                  '<div class="alert alert-danger d-flex align-items-center p-5 mb-5">' +
                  '<i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>' +
                  '<div class="d-flex flex-column"><span class="fw-bold fs-5">' + message + '</span></div>' +
                  '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>' +
                  '</div>';
      };

      // ── Public API ───────────────────────────────────────────────────
      return {
            init: function () {
                  stepper = document.querySelector("#kt_create_employee_stepper");
                  if (!stepper) return;

                  form = stepper.querySelector("#kt_create_employee_form");
                  submitBtn = document.getElementById("btn_submit");
                  nextBtn = document.getElementById("btn_next");
                  prevBtn = document.getElementById("btn_prev");

                  if (!form || !submitBtn || !nextBtn || !prevBtn) {
                        console.error("BidaEmployeeCreate: required DOM elements not found.");
                        return;
                  }

                  initStepper();
                  initValidation();
                  handleSubmit();
                  initDatePickers();
                  initGradeChange();
                  initOpeningBalanceCompute();
                  initCpfAccountNoFilter();
            }
      };

})();

KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeCreate.init();
});
