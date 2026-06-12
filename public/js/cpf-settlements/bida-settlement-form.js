"use strict";

// =========================================================================
// BidaSettlementForm — create & edit settlement form.
//
// - Employee (create: Select2; edit: fixed id from config) + settlement date
//   drive a live payout preview fetched from the preview endpoint.
// - The eligibility flags returned by the preview (active / open settlement /
//   pending advance work) surface a warning and disable submission.
// - "Deceased" type marks the payee (nominee) as required.
// - Submit posts multipart via fetch; edit spoofs PUT with _method so the
//   file upload is parsed (PUT multipart is not parsed by PHP).
//
// Driven by BidaSettlementFormConfig (see the Blade).
// =========================================================================
var BidaSettlementForm = (function () {
    var cfg, form, submitBtn, busy = false, blocked = false;

    var $id = function (id) { return id ? document.getElementById(id) : null; };
    var money = function (n) { return "৳ " + (parseInt(n, 10) || 0).toLocaleString("en-US"); };

    var employeeId = function () {
        if (cfg.mode === "edit") { return cfg.employeeId; }
        var sel = $id(cfg.employeeSelectId);
        return sel && sel.value ? sel.value : null;
    };

    var settlementDate = function () {
        var el = $id(cfg.settlementDateId);
        return el ? String(el.value).trim() : "";
    };

    // ---- Live preview ---------------------------------------------------
    var setPreview = function (closing, outstanding, payable) {
        var c = $id(cfg.preview.closingId), o = $id(cfg.preview.outstandingId), p = $id(cfg.preview.payableId);
        if (c) { c.textContent = money(closing); }
        if (o) { o.textContent = money(outstanding); }
        if (p) { p.textContent = money(payable); }
    };

    var showWarning = function (text) {
        var box = $id(cfg.preview.warnId), txt = $id(cfg.preview.warnTextId);
        if (txt) { txt.textContent = text; }
        if (box) { box.style.display = text ? "flex" : "none"; }
        blocked = !!text;
    };

    var fetchPreview = function () {
        var empId = employeeId();
        var date = settlementDate();

        if (!empId) { setPreview(0, 0, 0); showWarning(""); return; }

        var url = cfg.previewUrlBase + "/" + empId + (date ? "?date=" + encodeURIComponent(date) : "");

        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } })
            .then(function (res) { return res.json(); })
            .then(function (d) {
                setPreview(d.closing_balance, d.outstanding_advance, d.total_payable);

                if (!d.is_active) {
                    showWarning("This member is not active and cannot be settled.");
                } else if (d.has_open_settlement) {
                    showWarning("This member already has a settlement in progress or approved.");
                } else if (d.has_pending_advance_work) {
                    showWarning("Resolve the pending advance / recovery approval before settling this member.");
                } else {
                    showWarning("");
                }
            })
            .catch(function () { /* leave last good preview in place */ });
    };

    // ---- Deceased -> nominee required toggle ----------------------------
    var syncPayeeRequirement = function () {
        var sel = $id(cfg.typeSelectId);
        var isDeceased = sel && sel.value === "deceased";

        var mark = $id(cfg.payee.requiredMarkId);
        var hint = $id(cfg.payee.hintId);
        if (mark) { mark.style.display = isDeceased ? "inline" : "none"; }
        if (hint) {
            hint.textContent = isDeceased
                ? "Name the nominee who will receive the payout."
                : "Leave blank to pay the member. For a deceased member, name the nominee.";
        }
    };

    // ---- Validation -----------------------------------------------------
    var validate = function () {
        var errors = [];
        if (cfg.mode === "create" && !employeeId()) { errors.push("Select a member."); }

        var type = ($id(cfg.typeSelectId) || {}).value || "";
        if (!type) { errors.push("Select a settlement type."); }

        if (!($id(cfg.applicationDateId) || {}).value) { errors.push("Enter the application date."); }
        if (!settlementDate()) { errors.push("Enter the settlement date."); }

        if (type === "deceased" && !(($id(cfg.payee.nameId) || {}).value || "").trim()) {
            errors.push("A nominee / payee name is required for a deceased settlement.");
        }

        if (cfg.mode === "create") {
            var file = $id("stl_document");
            if (!file || !file.files || !file.files.length) {
                errors.push("Attach the supporting document (PDF).");
            }
        }
        return errors;
    };

    var setLoading = function (on) {
        busy = on;
        if (!submitBtn) { return; }
        if (on) { submitBtn.setAttribute("data-kt-indicator", "on"); submitBtn.disabled = true; }
        else { submitBtn.removeAttribute("data-kt-indicator"); submitBtn.disabled = false; }
    };

    var submit = function () {
        if (busy) { return; }

        if (blocked) {
            toastr.error("This member cannot be settled. Resolve the highlighted issue first.");
            return;
        }

        var errors = validate();
        if (errors.length) { toastr.error(errors[0]); return; }

        var fd = new FormData(form);
        if (cfg.spoofMethod) { fd.append("_method", cfg.spoofMethod); }

        setLoading(true);

        fetch(cfg.actionUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": cfg.csrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: fd
        })
            .then(function (res) {
                return res.json()
                    .then(function (b) { return { ok: res.ok, body: b }; })
                    .catch(function () { return { ok: res.ok, body: null }; });
            })
            .then(function (r) {
                if (r.ok && r.body && r.body.success) {
                    toastr.success(r.body.message || "Saved.");
                    window.location.href = r.body.redirect;
                    return;
                }
                setLoading(false);
                var msg = (r.body && r.body.message) || "Could not save the settlement.";
                if (r.body && r.body.errors) {
                    var first = Object.keys(r.body.errors)[0];
                    if (first) { msg = r.body.errors[first][0]; }
                }
                toastr.error(msg);
            })
            .catch(function () {
                setLoading(false);
                toastr.error("Network error. Please try again.");
            });
    };

    return {
        init: function (config) {
            cfg = config || {};
            form = $id(cfg.formId);
            if (!form) { return; }
            submitBtn = $id(cfg.submitId);

            // Date pickers
            if (window.flatpickr) {
                flatpickr("#" + cfg.applicationDateId, { dateFormat: "Y-m-d", maxDate: "today", allowInput: true });
                flatpickr("#" + cfg.settlementDateId, { dateFormat: "Y-m-d", allowInput: true, onChange: fetchPreview });
            } else {
                var sd = $id(cfg.settlementDateId);
                if (sd) { sd.addEventListener("change", fetchPreview); }
            }

            // Employee (create only — Select2 fires change through jQuery)
            if (cfg.mode === "create") {
                var emp = $id(cfg.employeeSelectId);
                if (emp) {
                    if (window.jQuery) { window.jQuery(emp).on("change", fetchPreview); }
                    else { emp.addEventListener("change", fetchPreview); }
                }
            }

            // Type -> payee requirement
            var typeSel = $id(cfg.typeSelectId);
            if (typeSel) {
                if (window.jQuery) { window.jQuery(typeSel).on("change", syncPayeeRequirement); }
                else { typeSel.addEventListener("change", syncPayeeRequirement); }
            }
            syncPayeeRequirement();

            // Edit: employee is fixed, so preview immediately from the saved date.
            if (cfg.mode === "edit") { fetchPreview(); }

            form.addEventListener("submit", function (e) { e.preventDefault(); submit(); });
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaSettlementFormConfig !== "undefined") {
        BidaSettlementForm.init(BidaSettlementFormConfig);
    }
});
