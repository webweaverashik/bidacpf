"use strict";

// =========================================================================
// BidaSettlementShow — settlement detail workflow actions.
// Driven by BidaSettlementShowConfig:
//   {
//     csrf,
//     actions: {
//       submit|approve|delete: { url, confirm, label },
//       reject: { url }
//     },
//     rejectReasonId
//   }
// submit/approve/reject fire a PUT; delete fires a DELETE. On success we
// toastr + either follow the server-provided redirect or reload so the
// status badge, audit trail and figures refresh.
// =========================================================================
var BidaSettlementShow = (function () {
    var cfg;

    var request = function (method, url, body, onDone) {
        fetch(url, {
            method: method,
            headers: {
                "X-CSRF-TOKEN": cfg.csrf,
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: body ? JSON.stringify(body) : null
        })
            .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
            .then(function (r) {
                if (r.ok && r.body && r.body.success) {
                    toastr.success(r.body.message || "Done.");
                    setTimeout(function () {
                        window.location.href = r.body.redirect || window.location.href;
                    }, 700);
                    return;
                }
                toastr.error((r.body && r.body.message) || "The action could not be completed.");
                if (onDone) { onDone(false); }
            })
            .catch(function () {
                toastr.error("Network error. Please try again.");
                if (onDone) { onDone(false); }
            });
    };

    var confirmThen = function (message, confirmLabel, proceed) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                text: message,
                icon: "warning",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: confirmLabel || "Yes, proceed",
                cancelButtonText: "Cancel",
                customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
            }).then(function (result) {
                if (result.isConfirmed) { proceed(); }
            });
            return;
        }
        if (window.confirm(message)) { proceed(); }
    };

    // ---- submit / approve (PUT) and delete (DELETE) ---------------------
    var bindActions = function () {
        var map = { submit: "PUT", approve: "PUT", delete: "DELETE" };

        Object.keys(map).forEach(function (key) {
            var btn = document.querySelector('[data-stl-action="' + key + '"]');
            var def = cfg.actions[key];
            if (!btn || !def) { return; }

            btn.addEventListener("click", function () {
                confirmThen(def.confirm, def.label, function () {
                    btn.setAttribute("data-kt-indicator", "on");
                    btn.disabled = true;
                    request(map[key], def.url, null, function () {
                        btn.removeAttribute("data-kt-indicator");
                        btn.disabled = false;
                    });
                });
            });
        });
    };

    // ---- reject (modal + reason) ----------------------------------------
    var bindReject = function () {
        var btn = document.querySelector('[data-stl-action="reject-confirm"]');
        var def = cfg.actions.reject;
        if (!btn || !def) { return; }

        btn.addEventListener("click", function () {
            var el = document.getElementById(cfg.rejectReasonId);
            var reason = el ? el.value : "";

            btn.setAttribute("data-kt-indicator", "on");
            btn.disabled = true;

            request("PUT", def.url, { reject_reason: reason }, function () {
                btn.removeAttribute("data-kt-indicator");
                btn.disabled = false;
            });
        });
    };

    return {
        init: function (config) {
            cfg = config || {};
            bindActions();
            bindReject();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaSettlementShowConfig !== "undefined") {
        BidaSettlementShow.init(BidaSettlementShowConfig);
    }
});
