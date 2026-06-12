"use strict";

// =========================================================================
// BidaInterestForm — create-batch form.
//
// Cut-off date is a constrained <select> (30 Jun / 31 Dec, FY 2025-26+). The
// fiscal year auto-fills whenever the selection changes. The fiscal year is
// derived from the selected option's VALUE (the Y-m-d date) using the same
// July-boundary rule as FiscalYearService, falling back to that even if the
// option's data-fy is missing — so the sync can't silently no-op.
//
// Submission is a single robust path (button click and Enter), with no
// FormValidation dependency. Driven by BidaInterestFormConfig:
//   { formId, submitId, dateId, fiscalYearId, storeUrl, csrf }
// =========================================================================
var BidaInterestForm = (function () {
    var cfg, form, submitBtn, busy = false;

    var byName = function (name) {
        return form.querySelector('[name="' + name + '"]');
    };

    var val = function (name) {
        var el = byName(name);
        return el ? String(el.value).trim() : "";
    };

    // Mirror FiscalYearService::fromDate (July boundary): YYYY-YYYY.
    var fiscalYearFromDate = function (dateStr) {
        if (!dateStr) { return ""; }
        var p = String(dateStr).split("-");
        var y = parseInt(p[0], 10), m = parseInt(p[1], 10);
        if (isNaN(y) || isNaN(m)) { return ""; }
        return m >= 7 ? (y + "-" + (y + 1)) : ((y - 1) + "-" + y);
    };

    var syncFiscalYear = function () {
        var sel = document.getElementById(cfg.dateId);
        var fy = document.getElementById(cfg.fiscalYearId);
        if (!sel || !fy) { return; }

        var opt = sel.options[sel.selectedIndex] || null;
        var value = sel.value || (opt ? opt.value : "");
        var dataFy = opt ? (opt.getAttribute("data-fy") || "") : "";

        // Prefer the server-provided data-fy; otherwise compute from the date.
        fy.value = dataFy || fiscalYearFromDate(value);
    };

    var validate = function () {
        var errors = [];

        if (!val("distribution_date")) {
            errors.push("Select a cut-off date (30 June or 31 December).");
        }
        if (!/^\d{4}-\d{4}$/.test(val("fiscal_year"))) {
            errors.push("Fiscal year is missing — pick a cut-off date first.");
        }
        var amt = val("total_interest_amount");
        if (amt === "" || isNaN(amt) || parseInt(amt, 10) < 1) {
            errors.push("Enter the total bank interest (a whole number of at least 1).");
        }
        return errors;
    };

    var setLoading = function (on) {
        busy = on;
        if (!submitBtn) { return; }
        if (on) {
            submitBtn.setAttribute("data-kt-indicator", "on");
            submitBtn.disabled = true;
        } else {
            submitBtn.removeAttribute("data-kt-indicator");
            submitBtn.disabled = false;
        }
    };

    var submit = function () {
        if (busy) { return; }

        var errors = validate();
        if (errors.length) {
            toastr.error(errors[0]);
            return;
        }

        setLoading(true);

        fetch(cfg.storeUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": cfg.csrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: new FormData(form)
        })
            .then(function (res) {
                return res.json()
                    .then(function (b) { return { ok: res.ok, body: b }; })
                    .catch(function () { return { ok: res.ok, body: null }; });
            })
            .then(function (r) {
                if (r.ok && r.body && r.body.success) {
                    toastr.success(r.body.message || "Distribution generated.");
                    window.location.href = r.body.redirect;
                    return;
                }
                setLoading(false);
                toastr.error((r.body && r.body.message) || "Could not generate the distribution.");
            })
            .catch(function () {
                setLoading(false);
                toastr.error("Network error. Please try again.");
            });
    };

    return {
        init: function (config) {
            cfg = config || {};
            form = document.getElementById(cfg.formId);
            if (!form) { return; }

            submitBtn = document.getElementById(cfg.submitId);

            var sel = document.getElementById(cfg.dateId);
            if (sel) {
                // Select2 dispatches its change through jQuery — a vanilla
                // addEventListener('change') never sees it. Bind via jQuery when
                // available so both Select2 and plain selects are covered.
                if (window.jQuery) {
                    window.jQuery(sel).on("change", syncFiscalYear);
                } else {
                    sel.addEventListener("change", syncFiscalYear);
                    sel.addEventListener("input", syncFiscalYear);
                }
                syncFiscalYear(); // reflect any pre-selected value on load
            }

            // Single submission path — covers button click and Enter key.
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                submit();
            });
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaInterestFormConfig !== "undefined") {
        BidaInterestForm.init(BidaInterestFormConfig);
    }
});