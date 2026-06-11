"use strict";

/*
|--------------------------------------------------------------------------
| BidaRecoveryForm
|--------------------------------------------------------------------------
| Drives the recovery draft create / edit form.
| Config (global `BidaRecoveryFormConfig`): { outstanding, isEdit }
*/
var BidaRecoveryForm = (function () {
    var cfg, form, validator;
    var el = {};

    function cacheElements() {
        el.recoveryDate = document.querySelector("#rec_recovery_date");
        el.depositDate  = document.querySelector("#rec_deposit_date");
        el.amount       = document.querySelector("#rec_amount");
        el.submitBtn    = document.querySelector("#rec_submit_btn");
    }

    function initFlatpickr() {
        if (typeof flatpickr === "undefined") return;
        if (el.recoveryDate) flatpickr(el.recoveryDate, { dateFormat: "Y-m-d", maxDate: "today", allowInput: true });
        if (el.depositDate)  flatpickr(el.depositDate,  { dateFormat: "Y-m-d", maxDate: "today", allowInput: true });
    }

    function initValidation() {
        if (typeof FormValidation === "undefined" || !form) return;

        var fields = {
            recovery_date: {
                validators: { notEmpty: { message: "Recovery date is required." } }
            },
            amount: {
                validators: {
                    notEmpty: { message: "Recovery amount is required." },
                    greaterThan: { min: 1, message: "Amount must be greater than zero." },
                    lessThan: {
                        max: cfg.outstanding,
                        inclusive: true,
                        message: "Amount cannot exceed the outstanding balance of " +
                            new Intl.NumberFormat("en-IN").format(cfg.outstanding) + "."
                    }
                }
            }
        };

        // Deposit slip is required only on create (kept on edit if untouched).
        if (!cfg.isEdit) {
            fields.deposit_slip = {
                validators: { notEmpty: { message: "A deposit slip must be attached." } }
            };
        }

        validator = FormValidation.formValidation(form, {
            fields: fields,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row" }),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        });

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
            form = document.querySelector("#rec_form");
            if (!form) return;
            cacheElements();
            initFlatpickr();
            initValidation();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaRecoveryFormConfig !== "undefined") {
        BidaRecoveryForm.init(BidaRecoveryFormConfig);
    }
});
