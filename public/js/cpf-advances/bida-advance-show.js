"use strict";

/*
|--------------------------------------------------------------------------
| BidaAdvanceShow
|--------------------------------------------------------------------------
| Detail-page interactions for advances & recoveries.
|   - [data-kt-confirm] elements -> Swal confirm, then submit their form.
|       data-kt-confirm   = confirmation message
|       data-kt-confirm-title (optional)
|       data-kt-confirm-icon  (optional: warning|question|success)
|       data-form         = selector of the form to submit (default: closest form)
|   - Reschedule modal -> live per-installment preview from outstanding balance.
*/
var BidaAdvanceShow = (function () {

    function initConfirms() {
        document.querySelectorAll("[data-kt-confirm]").forEach(function (btn) {
            btn.addEventListener("click", function (e) {
                e.preventDefault();

                var message = btn.getAttribute("data-kt-confirm");
                var title   = btn.getAttribute("data-kt-confirm-title") || "Are you sure?";
                var icon    = btn.getAttribute("data-kt-confirm-icon") || "warning";

                var formSel = btn.getAttribute("data-form");
                var form    = formSel ? document.querySelector(formSel) : btn.closest("form");

                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: "Yes, proceed",
                    cancelButtonText: "Cancel",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33"
                }).then(function (result) {
                    if (result.isConfirmed && form) {
                        form.submit();
                    }
                });
            });
        });
    }

    function initReschedulePreview() {
        var input   = document.querySelector("#resched_installments");
        var preview = document.querySelector("#resched_preview");
        if (!input || !preview) return;

        var outstanding = parseInt(input.dataset.outstanding || "0", 10);

        function update() {
            var count = parseInt(input.value || "0", 10);
            if (count > 0 && outstanding > 0) {
                var per = Math.ceil(outstanding / count);
                preview.textContent = new Intl.NumberFormat("en-IN").format(per);
            } else {
                preview.textContent = "—";
            }
        }

        input.addEventListener("input", update);
        update();
    }

    return {
        init: function () {
            initConfirms();
            initReschedulePreview();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    BidaAdvanceShow.init();
});
