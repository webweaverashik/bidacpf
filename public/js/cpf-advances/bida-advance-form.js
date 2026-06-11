"use strict";

/*
|--------------------------------------------------------------------------
| BidaAdvanceForm
|--------------------------------------------------------------------------
| Drives the CPF Advance draft create / edit form.
|   - Select2 on the employee picker (jQuery change event required).
|   - AJAX eligibility lookup -> shows balance + max limit, caps the amount.
|   - Flatpickr on the application date.
|   - FormValidation.js with explicit guards.
|
| Expects a global `BidaAdvanceFormConfig` inlined in the Blade:
|   { eligibilityUrl, isEdit, maxInstallments, eligibleAmount }
| where eligibilityUrl contains the token __ID__ to be replaced by employee id.
*/
var BidaAdvanceForm = (function () {
    var cfg, form, validator;

    var el = {};

    function cacheElements() {
        el.employee     = document.querySelector("#adv_employee_id");
        el.amount       = document.querySelector("#adv_requested_amount");
        el.rate         = document.querySelector("#adv_interest_rate");
        el.installments = document.querySelector("#adv_installment_count");
        el.appDate      = document.querySelector("#adv_application_date");
        el.balanceBox   = document.querySelector("#adv_balance_box");
        el.balanceVal   = document.querySelector("#adv_balance_value");
        el.eligibleVal  = document.querySelector("#adv_eligible_value");
        el.submitBtn    = document.querySelector("#adv_submit_btn");

        el.calc          = document.querySelector("#adv_calc");
        el.calcPrincipal = document.querySelector("#adv_calc_principal");
        el.calcRate      = document.querySelector("#adv_calc_rate");
        el.calcPer       = document.querySelector("#adv_calc_installment");
        el.calcLast      = document.querySelector("#adv_calc_last");
        el.calcLastWrap  = document.querySelector("#adv_calc_last_wrap");
        el.calcTotal     = document.querySelector("#adv_calc_total");
        el.calcInterest  = document.querySelector("#adv_calc_interest");
    }

    function fmt(n) {
        return new Intl.NumberFormat("en-IN").format(n || 0);
    }

    function updateCalc() {
        if (!el.calc) return;

        var amount = parseInt(el.amount && el.amount.value ? el.amount.value : "0", 10);
        var rate   = parseFloat(el.rate && el.rate.value ? el.rate.value : "0");
        var count  = parseInt(el.installments && el.installments.value ? el.installments.value : "0", 10);

        if (amount > 0 && count > 0) {
            var interest = Math.round(amount * (isNaN(rate) ? 0 : rate) / 100);
            var total    = amount + interest;
            var per      = Math.ceil(total / count);
            var last     = total - per * (count - 1);

            if (el.calcPrincipal) el.calcPrincipal.textContent = fmt(amount);
            if (el.calcInterest)  el.calcInterest.textContent  = fmt(interest);
            if (el.calcRate)      el.calcRate.textContent      = (isNaN(rate) ? 0 : rate);
            if (el.calcTotal)     el.calcTotal.textContent     = fmt(total);
            if (el.calcPer)       el.calcPer.textContent       = fmt(per) + " × " + count;

            // Show the final-installment note only when rounding makes it differ.
            if (el.calcLastWrap) {
                if (count > 1 && last !== per && last > 0) {
                    el.calcLast.textContent = fmt(last);
                    el.calcLastWrap.classList.remove("d-none");
                } else {
                    el.calcLastWrap.classList.add("d-none");
                }
            }
            el.calc.classList.remove("d-none");
        } else {
            el.calc.classList.add("d-none");
        }
    }

    function initCalc() {
        ["amount", "rate", "installments"].forEach(function (k) {
            if (el[k]) el[k].addEventListener("input", updateCalc);
        });
        updateCalc();
    }

    function initSelect2() {
        if (!el.employee) return;

        $(el.employee).select2({ placeholder: "Select employee", width: "100%" });

        // Select2 fires its own jQuery "change" — vanilla addEventListener misses it.
        $(el.employee).on("change", function () {
            var id = this.value;
            if (id) {
                fetchEligibility(id);
            } else {
                hideEligibility();
            }
        });

        // Edit screen: employee is fixed but we still want its limit shown.
        if (cfg.isEdit && el.employee.value) {
            fetchEligibility(el.employee.value);
        }
    }

    function fetchEligibility(employeeId) {
        var url = cfg.eligibilityUrl.replace("__ID__", employeeId);

        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                el.balanceVal.textContent  = fmt(data.balance);
                el.eligibleVal.textContent = fmt(data.eligible_amount);
                el.balanceBox.classList.remove("d-none");
                if (el.amount) {
                    el.amount.setAttribute("max", data.eligible_amount);
                    el.amount.dataset.eligible = data.eligible_amount;
                }
            })
            .catch(function () {
                toastr.error("Could not load the employee's eligibility.");
            });
    }

    function hideEligibility() {
        if (el.balanceBox) el.balanceBox.classList.add("d-none");
    }

    function initFlatpickr() {
        if (el.appDate && typeof flatpickr !== "undefined") {
            flatpickr(el.appDate, { dateFormat: "Y-m-d", maxDate: "today", allowInput: true });
        }
    }

    function initValidation() {
        if (typeof FormValidation === "undefined" || !form) return;

        var fields = {
            application_date: {
                validators: { notEmpty: { message: "Application date is required." } }
            },
            requested_amount: {
                validators: {
                    notEmpty: { message: "Advance amount is required." },
                    greaterThan: { min: 1, message: "Amount must be greater than zero." },
                    callback: {
                        message: "Amount exceeds the eligible limit.",
                        callback: function (input) {
                            var max = el.amount ? parseInt(el.amount.dataset.eligible || "0", 10) : 0;
                            if (!max) return true; // limit not loaded yet — server still enforces
                            return parseInt(input.value || "0", 10) <= max;
                        }
                    }
                }
            },
            interest_rate: {
                validators: {
                    notEmpty: { message: "Interest rate is required." },
                    between: { min: 0, max: 100, message: "Rate must be between 0 and 100." }
                }
            },
            installment_count: {
                validators: {
                    notEmpty: { message: "Installment count is required." },
                    between: { min: 1, max: cfg.maxInstallments, message: "Installments must be between 1 and " + cfg.maxInstallments + "." }
                }
            }
        };

        // Employee is only a field on the create screen.
        if (!cfg.isEdit) {
            fields.employee_id = {
                validators: { notEmpty: { message: "Please select an employee." } }
            };
        }

        validator = FormValidation.formValidation(form, {
            fields: fields,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".fv-row",
                    eleInvalidClass: "",
                    eleValidClass: ""
                }),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        });

        // Select2 needs the jQuery revalidate hook (create screen only).
        if (el.employee) {
            $(el.employee).on("change", function () {
                validator.revalidateField("employee_id");
            });
        }

        el.submitBtn.addEventListener("click", function (e) {
            e.preventDefault();
            validator.validate().then(function (status) {
                if (status === "Valid") {
                    el.submitBtn.setAttribute("data-kt-indicator", "on");
                    el.submitBtn.disabled = true;
                    form.submit();
                }
            });
        });
    }

    return {
        init: function (config) {
            cfg  = config || {};
            form = document.querySelector("#adv_form");
            if (!form) return;

            cacheElements();
            initSelect2();
            initFlatpickr();
            initValidation();
            initCalc();

            // Edit screen: no employee picker — load the fixed employee's limit.
            if (cfg.isEdit && cfg.employeeId) {
                fetchEligibility(cfg.employeeId);
            }
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaAdvanceFormConfig !== "undefined") {
        BidaAdvanceForm.init(BidaAdvanceFormConfig);
    }
});
