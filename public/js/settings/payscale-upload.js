"use strict";

// BIDA CPF — Pay Scale upload (meta + grade/step grid → preview → confirm).
var BidaPayScaleUpload = (function () {
    var cfg = window.BidaPayScaleUploadConfig || {};

    var form, elFile, btnPreview, btnCommit, btnReset,
        uploadCard, previewCard, gridHead, gridBody, invalidNote,
        sumGrades, sumSteps, sumInvalid;

    var token = null;
    var canCommit = false;

    function toast(type, msg) { if (window.toastr) toastr[type](msg); }

    function btnLoading(btn, on) {
        if (!btn) return;
        btn.setAttribute("data-kt-indicator", on ? "on" : "off");
        btn.disabled = on;
    }

    function esc(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function val(name) {
        var el = form.querySelector('[name="' + name + '"]');
        return el ? el.value.trim() : "";
    }

    function firstError(body) {
        if (body && body.errors) {
            var k = Object.keys(body.errors)[0];
            if (k) return body.errors[k][0];
        }
        return body && body.message ? body.message : null;
    }

    // ---- preview -----------------------------------------------------------

    function preview() {
        var file = elFile.files[0];

        if (!val("name")) { toast("error", "Please enter a name."); return; }
        if (!val("effective_year")) { toast("error", "Please enter the effective year."); return; }
        if (!val("effective_from")) { toast("error", "Please select the effective-from date."); return; }
        if (!file) { toast("error", "Please choose a spreadsheet to upload."); return; }

        var fd = new FormData();
        fd.append("name", val("name"));
        fd.append("effective_year", val("effective_year"));
        fd.append("effective_from", val("effective_from"));
        fd.append("effective_to", val("effective_to"));
        fd.append("is_active", form.querySelector('[name="is_active"]').checked ? 1 : 0);
        fd.append("file", file);

        btnLoading(btnPreview, true);
        fetch(cfg.previewUrl, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": cfg.csrfToken, "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
            body: fd,
        })
            .then(function (r) {
                return r.json()
                    .then(function (b) { return { ok: r.ok, status: r.status, b: b }; })
                    .catch(function () { return { ok: false, status: r.status, parseError: true }; });
            })
            .then(function (res) {
                btnLoading(btnPreview, false);
                if (res.parseError) {
                    toast("error", "Unexpected server response (HTTP " + res.status + "). Check the file size limit and try again.");
                    return;
                }
                if (!res.ok || !res.b || !res.b.success) {
                    toast("error", firstError(res.b) || "Could not preview the file.");
                    return;
                }
                token = res.b.token;
                try {
                    renderGrid(res.b.summary, res.b.grid);
                } catch (e) {
                    console.error("Preview render failed:", e);
                    toast("error", "The preview could not be displayed. Please reload and try again.");
                }
            })
            .catch(function () { btnLoading(btnPreview, false); toast("error", "Network error. Please try again."); });
    }

    function renderGrid(summary, grid) {
        canCommit = !!summary.valid;

        sumGrades.textContent = summary.grades || 0;
        sumSteps.textContent = summary.steps || 0;
        sumInvalid.textContent = summary.invalid || 0;

        var maxStep = grid.max_step || 0;

        var head = '<tr class="fw-bold text-muted fs-8 text-uppercase text-center bg-light">'
            + '<th class="min-w-60px">Grade</th>'
            + '<th class="min-w-160px text-start">Pay Range</th>';
        for (var n = 1; n <= maxStep; n++) head += '<th class="min-w-90px text-end">Step ' + n + "</th>";
        head += '<th class="min-w-150px text-start">Status</th></tr>';
        gridHead.innerHTML = head;

        var body = "";
        (grid.rows || []).forEach(function (row) {
            var cells = "";
            for (var n = 1; n <= maxStep; n++) {
                var v = row.cells[String(n)];
                cells += '<td class="text-end">' + (v ? esc(v) : '<span class="text-muted">—</span>') + "</td>";
            }

            var status = row.valid
                ? '<span class="badge badge-light-success">Valid</span>'
                : '<span class="badge badge-light-danger mb-1">Invalid</span>'
                    + '<div class="text-danger fs-8">'
                    + (row.errors || []).map(function (e) { return "• " + esc(e); }).join("<br>")
                    + "</div>";

            body += '<tr class="' + (row.valid ? "" : "bg-light-danger") + '">'
                + '<td class="text-center fw-bold bg-light-primary">' + esc(row.grade != null ? row.grade : "?") + "</td>"
                + "<td>" + esc(row.range) + "</td>"
                + cells
                + "<td>" + status + "</td>"
                + "</tr>";
        });
        gridBody.innerHTML = body;

        invalidNote.classList.toggle("d-none", !(summary.invalid > 0));
        invalidNote.classList.toggle("d-flex", summary.invalid > 0);
        btnCommit.disabled = !canCommit;

        uploadCard.classList.add("d-none");
        previewCard.classList.remove("d-none");
        previewCard.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    // ---- commit ------------------------------------------------------------

    function commit() {
        if (!token) { toast("error", "Please preview a file first."); return; }
        if (!canCommit) { toast("error", "Fix the highlighted grades before creating the pay scale."); return; }

        Swal.fire({
            text: "Create this pay scale? It cannot be edited afterwards.",
            icon: "question",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Yes, create",
            cancelButtonText: "Cancel",
            customClass: { confirmButton: "btn btn-success", cancelButton: "btn btn-light" },
        }).then(function (result) {
            if (!result.isConfirmed) return;

            btnLoading(btnCommit, true);
            fetch(cfg.commitUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": cfg.csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ token: token }),
            })
                .then(function (r) { return r.json().then(function (b) { return { ok: r.ok, b: b }; }); })
                .then(function (res) {
                    btnLoading(btnCommit, false);
                    if (!res.ok || !res.b.success) {
                        toast("error", firstError(res.b) || "Could not create the pay scale.");
                        return;
                    }
                    toast("success", res.b.message);
                    setTimeout(function () {
                        window.location.href = res.b.show_url || cfg.indexUrl;
                    }, 700);
                })
                .catch(function () { btnLoading(btnCommit, false); toast("error", "Network error during creation."); });
        });
    }

    // ---- reset -------------------------------------------------------------

    function reset() {
        token = null;
        canCommit = false;
        elFile.value = "";
        gridBody.innerHTML = "";
        gridHead.innerHTML = "";
        btnCommit.disabled = true;
        previewCard.classList.add("d-none");
        uploadCard.classList.remove("d-none");
        uploadCard.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    // ---- init --------------------------------------------------------------

    return {
        init: function () {
            uploadCard = document.getElementById("upload_card");
            previewCard = document.getElementById("preview_card");
            if (!uploadCard) return;

            form = document.getElementById("payscale_form");
            elFile = document.getElementById("upload_file");
            btnPreview = document.getElementById("btn_preview");
            btnCommit = document.getElementById("btn_commit");
            btnReset = document.getElementById("btn_reset");
            gridHead = document.getElementById("grid_head");
            gridBody = document.getElementById("grid_body");
            invalidNote = document.getElementById("invalid_note");
            sumGrades = document.getElementById("sum_grades");
            sumSteps = document.getElementById("sum_steps");
            sumInvalid = document.getElementById("sum_invalid");

            if (window.flatpickr) {
                form.querySelectorAll(".js-flatpickr").forEach(function (el) {
                    flatpickr(el, { dateFormat: "Y-m-d", allowInput: true });
                });
            }

            btnPreview.addEventListener("click", preview);
            btnCommit.addEventListener("click", commit);
            btnReset.addEventListener("click", reset);
        },
    };
})();

KTUtil.onDOMContentLoaded(function () {
    BidaPayScaleUpload.init();
});
