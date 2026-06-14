"use strict";

// =========================================================================
// BidaReport — single-page Reporting hub.
//
//   • A grouped Select2 (#report_select) chooses a report.
//   • On change we AJAX-load that report's parameter panel (params endpoint),
//     (re)initialise its Select2 / Flatpickr controls, reveal the action bar
//     and filter the Download menu to the formats the report supports.
//   • Preview posts the collected params to the preview endpoint and injects
//     the returned table / certificate HTML into the preview pane.
//   • Download builds a querystring for the generate endpoint and navigates
//     to it (streamed file), mirroring the export pattern used elsewhere.
//
// Driven by BidaReportConfig (see reports/index.blade.php). Vanilla DOM +
// fetch + toastr; jQuery is used only where Select2 requires it.
// =========================================================================
var BidaReport = (function () {
    var cfg;
    var els = {};
    var current = null;       // { key, kind, formats }
    var busyPreview = false;

    var $id = function (id) { return document.getElementById(id); };

    // ---- small helpers --------------------------------------------------
    var setText = function (el, text) { if (el) { el.textContent = text || ""; } };

    var show = function (el) { if (el) { el.classList.remove("d-none"); } };
    var hide = function (el) { if (el) { el.classList.add("d-none"); } };

    var jq = function (el) { return window.jQuery ? window.jQuery(el) : null; };

    // ---- parameter collection ------------------------------------------
    var collectParams = function () {
        var out = {};
        if (!els.params) { return out; }
        els.params.querySelectorAll("[data-report-param]").forEach(function (ctrl) {
            var key = ctrl.getAttribute("data-report-param");
            var val = (ctrl.value || "").trim();
            if (key && val !== "") { out[key] = val; }
        });
        return out;
    };

    var queryString = function (extra) {
        var params = collectParams();
        var parts = ["report=" + encodeURIComponent(current.key)];
        Object.keys(params).forEach(function (k) {
            parts.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
        });
        if (extra) {
            Object.keys(extra).forEach(function (k) {
                parts.push(encodeURIComponent(k) + "=" + encodeURIComponent(extra[k]));
            });
        }
        return parts.join("&");
    };

    // ---- init Select2 / Flatpickr inside the freshly-injected panel -----
    var initParamControls = function () {
        // Select2 — anchor the dropdown to the panel so the card doesn't clip it.
        if (window.jQuery) {
            els.params.querySelectorAll("[data-rpt-select2]").forEach(function (sel) {
                var $sel = window.jQuery(sel);
                $sel.select2({
                    placeholder: sel.getAttribute("data-placeholder") || "Select…",
                    allowClear: sel.getAttribute("data-allow-clear") === "true",
                    dropdownParent: window.jQuery(els.params),
                    width: "100%"
                });
            });
        }

        // Flatpickr date inputs.
        if (window.flatpickr) {
            els.params.querySelectorAll("[data-rpt-flatpickr]").forEach(function (inp) {
                window.flatpickr(inp, { dateFormat: "Y-m-d", allowInput: true });
            });
        }
    };

    // ---- filter the Download menu to supported formats ------------------
    var syncDownloadMenu = function (formats) {
        var menu = $id("report_download_menu");
        if (!menu) { return; }
        var allowed = formats || ["pdf"];
        menu.querySelectorAll("[data-format]").forEach(function (item) {
            var fmt = item.getAttribute("data-format");
            item.style.display = allowed.indexOf(fmt) === -1 ? "none" : "";
        });
    };

    // ---- preview pane state ---------------------------------------------
    var resetPreview = function () {
        if (els.preview) {
            els.preview.innerHTML =
                '<div class="rpt-empty text-muted text-center py-20">' +
                '<i class="ki-duotone ki-document fs-3x text-gray-300 mb-3">' +
                '<span class="path1"></span><span class="path2"></span></i>' +
                '<div class="fs-6">No preview yet.</div>' +
                '<div class="fs-7">Set the options and click <span class="fw-semibold">Preview</span>.</div>' +
                "</div>";
        }
        setText(els.previewTitle, "Preview");
        setText(els.previewSubtitle, "The selected report will appear here.");
    };

    // Full reset of the sub-filters + preview. Called whenever the report type
    // changes so no stale filter values (or a previous report's preview) linger.
    var resetFilters = function () {
        if (els.params) {
            // Tear down any Select2 instances before wiping the panel.
            if (window.jQuery) {
                els.params.querySelectorAll("[data-rpt-select2]").forEach(function (sel) {
                    try { window.jQuery(sel).select2("destroy"); } catch (e) { }
                });
            }
            els.params.innerHTML = "";
        }
        hide(els.actions);
        setText(els.desc, "");
        hide(els.desc);
        current = null;
        resetPreview();
    };

    // ---- load parameter panel for the chosen report --------------------
    var loadParams = function (key) {
        var url = cfg.paramsUrl + "?report=" + encodeURIComponent(key);

        els.params.innerHTML =
            '<div class="text-center py-10"><span class="spinner-border spinner-border-sm text-primary"></span></div>';

        fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" } })
            .then(function (res) {
                if (!res.ok) { throw new Error("Could not load report options."); }
                return res.json();
            })
            .then(function (d) {
                current = { key: key, kind: d.kind, formats: d.formats || ["pdf"] };

                els.params.innerHTML = d.html;
                initParamControls();

                // Description line
                if (d.desc) { setText(els.desc, d.desc); show(els.desc); } else { hide(els.desc); }

                syncDownloadMenu(d.formats);
                show(els.actions);

                // Re-init KTMenu so the Download dropdown works after reveal.
                if (window.KTMenu && typeof KTMenu.createInstances === "function") {
                    KTMenu.createInstances();
                }

                resetPreview();
            })
            .catch(function () {
                els.params.innerHTML =
                    '<div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-4">' +
                    '<i class="ki-outline ki-information fs-2 text-danger me-3"></i>' +
                    '<div class="fs-7">Could not load this report\'s options. Please try again.</div></div>';
                hide(els.actions);
            });
    };

    // ---- preview --------------------------------------------------------
    var runPreview = function (page) {
        if (!current || busyPreview) { return; }
        busyPreview = true;

        var btn = els.actions ? els.actions.querySelector('[data-report-action="preview"]') : null;
        if (btn) { btn.setAttribute("data-kt-indicator", "on"); btn.disabled = true; }

        els.preview.innerHTML =
            '<div class="text-center py-20"><span class="spinner-border text-primary"></span>' +
            '<div class="text-muted fs-7 mt-3">Building preview…</div></div>';

        var extra = (page && page > 1) ? { page: page } : null;

        fetch(cfg.previewUrl + "?" + queryString(extra), {
            headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" }
        })
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, body: body }; });
            })
            .then(function (r) {
                if (!r.ok) {
                    var msg = (r.body && r.body.error) || "Could not generate the preview.";
                    toastr.error(msg);
                    els.preview.innerHTML =
                        '<div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">' +
                        '<i class="ki-outline ki-information fs-2 text-warning me-3"></i>' +
                        '<div class="fs-7">' + msg + "</div></div>";
                    return;
                }

                els.preview.innerHTML = r.body.html || "";

                // Lift title/subtitle off the injected node's data attributes.
                var node = els.preview.querySelector(".rpt-preview");
                if (node) {
                    setText(els.previewTitle, node.getAttribute("data-preview-title") || "Preview");
                    setText(els.previewSubtitle, node.getAttribute("data-preview-subtitle") || "");
                }
            })
            .catch(function () {
                toastr.error("Network error while building the preview.");
                resetPreview();
            })
            .then(function () {
                busyPreview = false;
                if (btn) { btn.removeAttribute("data-kt-indicator"); btn.disabled = false; }
            });
    };

    // ---- download -------------------------------------------------------
    // Fetch the file as a blob so we can show a SweetAlert "please wait" while
    // the server renders it, then trigger a silent download — no full-page
    // navigation/reload. (SweetAlert ships with Metronic; no init needed.)
    var runDownload = function (fmt) {
        if (!current || !cfg.generateUrl) { return; }

        // Guard against requesting a format the report doesn't offer.
        if (current.formats.indexOf(fmt) === -1) {
            toastr.warning("This report is not available in that format.");
            return;
        }

        var hasSwal = typeof window.Swal !== "undefined";
        if (hasSwal) {
            Swal.fire({
                html: "Preparing your " + fmt.toUpperCase() + " download. Please wait…",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () { Swal.showLoading(); }
            });
        }

        var filenameFrom = function (res) {
            var cd = res.headers.get("Content-Disposition") || "";
            var m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd);
            return m ? decodeURIComponent(m[1]) : (current.key + "." + fmt);
        };

        fetch(cfg.generateUrl + "?" + queryString({ format: fmt }), {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
            .then(function (res) {
                if (!res.ok) {
                    // Surface the server's validation message when it sent JSON.
                    return res.text().then(function (txt) {
                        var msg = "Could not generate the download.";
                        try { var j = JSON.parse(txt); if (j && (j.message || j.error)) { msg = j.message || j.error; } } catch (e) { }
                        throw new Error(msg);
                    });
                }
                return res.blob().then(function (blob) { return { blob: blob, name: filenameFrom(res) }; });
            })
            .then(function (d) {
                var url = window.URL.createObjectURL(d.blob);
                var a = document.createElement("a");
                a.href = url;
                a.download = d.name;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                if (hasSwal) { Swal.close(); }
                toastr.success("Your " + fmt.toUpperCase() + " is ready.");
            })
            .catch(function (err) {
                if (hasSwal) { Swal.close(); }
                toastr.error((err && err.message) || "Could not generate the download.");
            });
    };

    // ---- wire up --------------------------------------------------------
    return {
        init: function (config) {
            cfg = config || {};

            els.select = $id("report_select");
            els.desc = $id("report_desc");
            els.params = $id("report_params");
            els.actions = $id("report_actions");
            els.preview = $id("report_preview");
            els.previewTitle = $id("report_preview_title");
            els.previewSubtitle = $id("report_preview_subtitle");

            if (!els.select) { return; }

            // Report chooser (Select2 → jQuery change).
            var onSelect = function () {
                var key = els.select.value;
                resetFilters();          // always start from a clean slate
                if (!key) { return; }
                loadParams(key);
            };
            if (window.jQuery) { window.jQuery(els.select).on("change", onSelect); }
            else { els.select.addEventListener("change", onSelect); }

            // Action bar (delegated; the bar is revealed after params load).
            if (els.actions) {
                els.actions.addEventListener("click", function (e) {
                    var trigger = e.target.closest("[data-report-action]");
                    if (!trigger) { return; }
                    var action = trigger.getAttribute("data-report-action");

                    if (action === "preview") {
                        e.preventDefault();
                        runPreview(1);
                    } else if (action === "download") {
                        e.preventDefault();
                        runDownload(trigger.getAttribute("data-fmt"));
                    }
                });
            }

            // AJAX pagination (delegated — the bar is re-rendered each preview).
            if (els.preview) {
                els.preview.addEventListener("click", function (e) {
                    var link = e.target.closest("[data-rpt-page]");
                    if (!link) { return; }
                    e.preventDefault();
                    if (link.closest(".disabled")) { return; }
                    var page = parseInt(link.getAttribute("data-rpt-page"), 10) || 1;
                    runPreview(page);
                });
            }
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaReportConfig !== "undefined") {
        BidaReport.init(BidaReportConfig);
    }
});