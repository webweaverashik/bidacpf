"use strict";

// BIDA CPF — Employee bulk upload (preview → confirm).
var BidaEmployeeUpload = (function () {
      var cfg = window.BidaEmployeeUploadConfig || {};

      var elPayScale, elFile, btnPreview, btnCommit, btnReset,
            uploadCard, previewCard,
            sumTotal, sumValid, sumInvalid;

      var token = null;
      var validCount = 0;
      var previewTable = null;

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

      // ---- preview -----------------------------------------------------------

      function preview() {
            var payScaleId = elPayScale.value;
            var file = elFile.files[0];

            if (!payScaleId) { toast("error", "Please select a pay scale."); return; }
            if (!file) { toast("error", "Please choose a file to upload."); return; }

            var fd = new FormData();
            fd.append("file", file);
            fd.append("pay_scale_id", payScaleId);

            btnLoading(btnPreview, true);
            fetch(cfg.previewUrl, {
                  method: "POST",
                  headers: { "X-CSRF-TOKEN": cfg.csrfToken, "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                  body: fd,
            })
                  .then(function (r) {
                        return r.json()
                              .then(function (b) { return { ok: r.ok, status: r.status, b: b }; })
                              .catch(function () { return { ok: false, status: r.status, b: null, parseError: true }; });
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

                        // Render errors must not be reported as a network failure.
                        try {
                              renderPreview(res.b.summary, res.b.rows);
                        } catch (e) {
                              console.error("Preview render failed:", e);
                              toast("error", "The preview table could not be displayed. Please reload the page and try again.");
                        }
                  })
                  .catch(function () {
                        btnLoading(btnPreview, false);
                        toast("error", "Network error. Please try again.");
                  });
      }

      function firstError(body) {
            if (body && body.errors) {
                  var k = Object.keys(body.errors)[0];
                  if (k) return body.errors[k][0];
            }
            return body && body.message ? body.message : null;
      }

      function renderPreview(summary, rows) {
            validCount = summary.valid || 0;

            sumTotal.textContent = summary.total || 0;
            sumValid.textContent = summary.valid || 0;
            sumInvalid.textContent = summary.invalid || 0;

            var textRender = $.fn.dataTable.render.text();

            if (previewTable) {
                  previewTable.clear().rows.add(rows).draw();
            } else {
                  previewTable = $("#preview_table").DataTable({
                        data: rows,
                        order: [[0, "asc"]],
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                        columns: [
                              { data: "row_no" },
                              { data: "cpf_account_no", className: "text-gray-900 fw-bold", render: textRender },
                              { data: "name", render: textRender },
                              { data: "designation", render: textRender },
                              {
                                    data: null, className: "text-center", searchable: false,
                                    render: function (row) {
                                          return (row.grade != null ? row.grade : "?") + " / " + (row.step != null ? row.step : "?");
                                    },
                              },
                              { data: "basic_salary", className: "text-end" },
                              { data: "joining_date", render: textRender },
                              { data: "net", className: "text-end" },
                              {
                                    data: null, orderable: false, searchable: false,
                                    render: function (row) {
                                          if (row.valid) {
                                                return '<span class="badge badge-light-success"><i class="ki-outline ki-check fs-6 me-1"></i>Valid</span>';
                                          }
                                          var msgs = (row.errors || []).map(function (e) { return "<div>• " + esc(e) + "</div>"; }).join("");
                                          return '<span class="badge badge-light-danger mb-1"><i class="ki-outline ki-cross fs-6 me-1"></i>Invalid</span>'
                                                + '<div class="text-danger fs-8">' + msgs + "</div>";
                                    },
                              },
                        ],
                        createdRow: function (tr, data) {
                              if (!data.valid) tr.classList.add("bg-light-danger");
                        },
                        // Search top-right; length + info bottom-left, pagination bottom-right.
                        dom:
                              "<'row mb-2'<'col-sm-6'><'col-sm-6 d-flex justify-content-end'f>>" +
                              "<'row'<'col-sm-12'tr>>" +
                              "<'row mt-3'<'col-sm-6 d-flex align-items-center'li><'col-sm-6 d-flex justify-content-end'p>>",
                        language: { search: "", searchPlaceholder: "Search rows" },
                  });
            }

            btnCommit.disabled = validCount === 0;

            uploadCard.classList.add("d-none");
            previewCard.classList.remove("d-none");
            previewCard.scrollIntoView({ behavior: "smooth", block: "start" });
      }

      // ---- commit ------------------------------------------------------------

      function commit() {
            if (!token) { toast("error", "Please preview a file first."); return; }
            if (validCount === 0) { toast("error", "There are no valid rows to import."); return; }

            Swal.fire({
                  text: "Import " + validCount + " valid employee(s)? Invalid rows will be skipped.",
                  icon: "question",
                  showCancelButton: true,
                  buttonsStyling: false,
                  confirmButtonText: "Yes, import",
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
                                    toast("error", firstError(res.b) || "Import failed.");
                                    return;
                              }
                              Swal.fire({
                                    text: res.b.message,
                                    icon: "success",
                                    buttonsStyling: false,
                                    showCancelButton: true,
                                    confirmButtonText: "View employees",
                                    cancelButtonText: "Upload another",
                                    customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" },
                              }).then(function (r2) {
                                    if (r2.isConfirmed) window.location.href = cfg.employeesUrl;
                                    else reset();
                              });
                        })
                        .catch(function () { btnLoading(btnCommit, false); toast("error", "Network error during import."); });
            });
      }

      // ---- reset -------------------------------------------------------------

      function reset() {
            token = null;
            validCount = 0;
            elFile.value = "";
            if (previewTable) previewTable.clear().draw();
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

                  elPayScale = document.getElementById("pay_scale_id");
                  elFile = document.getElementById("upload_file");
                  btnPreview = document.getElementById("btn_preview");
                  btnCommit = document.getElementById("btn_commit");
                  btnReset = document.getElementById("btn_reset");
                  sumTotal = document.getElementById("sum_total");
                  sumValid = document.getElementById("sum_valid");
                  sumInvalid = document.getElementById("sum_invalid");

                  btnPreview.addEventListener("click", preview);
                  btnCommit.addEventListener("click", commit);
                  btnReset.addEventListener("click", reset);
            },
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaEmployeeUpload.init();
});