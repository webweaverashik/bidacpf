"use strict";

// BIDA CPF — Pay Scale activate/deactivate (used on the list and the detail page).
var BidaPayScaleIndex = (function () {
    var cfg = window.BidaPayScaleConfig || {};

    function toast(type, msg) { if (window.toastr) toastr[type](msg); }

    function toggle(btn) {
        var isActive = btn.getAttribute("data-active") === "1";
        var name = btn.getAttribute("data-name") || "this pay scale";

        var text = isActive
            ? 'Deactivate "' + name + '"?'
            : 'Make "' + name + '" the active pay scale? Any other active scale will be deactivated.';

        Swal.fire({
            text: text,
            icon: isActive ? "warning" : "question",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: isActive ? "Yes, deactivate" : "Yes, activate",
            cancelButtonText: "Cancel",
            customClass: {
                confirmButton: "btn " + (isActive ? "btn-warning" : "btn-success"),
                cancelButton: "btn btn-light",
            },
        }).then(function (result) {
            if (!result.isConfirmed) return;

            btn.disabled = true;
            fetch(btn.getAttribute("data-url"), {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": cfg.csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            })
                .then(function (r) { return r.json().then(function (b) { return { ok: r.ok, b: b }; }); })
                .then(function (res) {
                    if (!res.ok || !res.b.success) {
                        btn.disabled = false;
                        toast("error", (res.b && res.b.message) || "Could not update status.");
                        return;
                    }
                    toast("success", res.b.message);
                    // Activation affects other rows too — reload for a consistent view.
                    setTimeout(function () { window.location.reload(); }, 600);
                })
                .catch(function () { btn.disabled = false; toast("error", "Network error. Please try again."); });
        });
    }

    return {
        init: function () {
            document.querySelectorAll(".js-toggle-payscale").forEach(function (b) {
                b.addEventListener("click", function () { toggle(b); });
            });
        },
    };
})();

KTUtil.onDOMContentLoaded(function () {
    BidaPayScaleIndex.init();
});