"use strict";

// =========================================================================
// BidaDashboard — renders the role-aware CPF dashboard.
//
// Driven by a `BidaDashboardConfig` object inlined in the Blade (see
// dashboard/partials/scripts.blade.php):
//   {
//     chartsUrl,                 // AJAX endpoint backing the fiscal-year switch
//     currency,                  // e.g. "৳"
//     fiscalYear,                // initial FY string
//     charts: {
//       months, fundGrowth,
//       employeeContribution, governmentContribution,
//       composition: { labels, values },
//       comparison: { contributions, recoveries, disbursed },
//       interest,                          // monthly bank interest distributed
//       advancePortfolio: { labels, values },
//       membersByGrade: { labels, values }
//     }
//   }
//
// Notes:
//   - ApexCharts ships with Metronic's global plugins.bundle.js.
//   - The fiscal-year control is a Select2 ([data-control="select2"]); Select2
//     fires its change through jQuery, so that one handler uses $(...).on(),
//     while everything else stays vanilla.
//   - On a fiscal-year change the AJAX response repaints the fiscal-year-scoped
//     charts AND the fiscal-year stat tiles. The "as of today" tiles, the
//     Advance Portfolio donut and the Members-by-grade chart are point-in-time
//     and stay put.
// =========================================================================
var BidaDashboard = (function () {
    var cfg;
    var charts = {}; // logical name -> ApexCharts instance
    var reducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

    var cssVar = function (name) {
        return (typeof KTUtil !== "undefined") ? KTUtil.getCssVariableValue(name) : "";
    };

    var fmtNumber = function (v) {
        return new Intl.NumberFormat("en-US").format(Math.round(v || 0));
    };

    var fmtMoney = function (v) {
        return (cfg.currency || "৳") + " " + fmtNumber(v);
    };

    // Compact, Bangladesh-style axis labels (Lakh / Crore).
    var fmtCompact = function (v) {
        v = Math.round(v || 0);
        var a = Math.abs(v);
        if (a >= 10000000) { return (v / 10000000).toFixed(2).replace(/\.00$/, "") + " Cr"; }
        if (a >= 100000) { return (v / 100000).toFixed(2).replace(/\.00$/, "") + " L"; }
        if (a >= 1000) { return (v / 1000).toFixed(1).replace(/\.0$/, "") + "K"; }
        return "" + v;
    };

    var palette = function () {
        return [
            cssVar("--bs-primary"),
            cssVar("--bs-success"),
            cssVar("--bs-warning"),
            cssVar("--bs-danger"),
            cssVar("--bs-info"),
            cssVar("--bs-primary-active") || cssVar("--bs-primary"),
            cssVar("--bs-gray-500"),
            cssVar("--bs-gray-700")
        ];
    };

    var labelColor = function () { return cssVar("--bs-gray-500"); };
    var borderColor = function () { return cssVar("--bs-gray-200"); };

    /* --------------------------------------------------------------------- */
    /* Animated values (stat tiles)                                          */
    /* --------------------------------------------------------------------- */

    // Tween an element's text from `from` to `to`, formatting as money or plain.
    var animateValue = function (el, to, money, from) {
        to = Math.round(to || 0);
        if (from === undefined || from === null) { from = 0; }

        if (reducedMotion) {
            el.textContent = money ? fmtMoney(to) : fmtNumber(to);
            el.setAttribute("data-bida-value", to);
            return;
        }

        var duration = 900;
        var start = null;
        var step = function (ts) {
            if (start === null) { start = ts; }
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
            var val = Math.round(from + (to - from) * eased);
            el.textContent = money ? fmtMoney(val) : fmtNumber(val);
            if (p < 1) { requestAnimationFrame(step); }
            else { el.setAttribute("data-bida-value", to); }
        };
        requestAnimationFrame(step);
    };

    var runCountUps = function () {
        document.querySelectorAll(".bida-countup").forEach(function (el) {
            var target = parseInt(el.getAttribute("data-bida-value") || "0", 10);
            var money = el.getAttribute("data-bida-money") === "1";
            animateValue(el, target, money, 0);
        });
    };

    // Update a single fiscal-year stat tile, tweening from its current value.
    var setStat = function (key, value, money) {
        var el = document.querySelector('[data-bida-stat="' + key + '"]');
        if (!el) { return; }
        var from = parseFloat(el.getAttribute("data-bida-value") || "0");
        animateValue(el, value, money, from);
    };

    /* --------------------------------------------------------------------- */
    /* Charts                                                                */
    /* --------------------------------------------------------------------- */

    var renderFundGrowth = function () {
        var el = document.getElementById("bida_chart_fund_growth");
        if (!el) { return; }
        var c = cfg.charts;

        charts.fund = new ApexCharts(el, {
            series: [{ name: "Fund Balance", data: c.fundGrowth }],
            chart: { type: "area", height: 350, fontFamily: "inherit", toolbar: { show: false } },
            colors: [cssVar("--bs-primary")],
            stroke: { curve: "smooth", width: 3 },
            fill: {
                type: "gradient",
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { size: 6 } },
            xaxis: {
                categories: c.months,
                labels: { style: { colors: labelColor(), fontSize: "11px" }, rotate: -45, rotateAlways: false },
                axisBorder: { show: false }, axisTicks: { show: false }, tickPlacement: "on"
            },
            yaxis: { labels: { style: { colors: labelColor() }, formatter: fmtCompact } },
            grid: { borderColor: borderColor(), strokeDashArray: 4 },
            tooltip: { y: { formatter: fmtMoney } },
            noData: { text: "No data for this period" }
        });
        charts.fund.render();
    };

    var renderContributions = function () {
        var el = document.getElementById("bida_chart_contributions");
        if (!el) { return; }
        var c = cfg.charts;

        charts.contrib = new ApexCharts(el, {
            series: [
                { name: "Employee", data: c.employeeContribution },
                { name: "Government", data: c.governmentContribution }
            ],
            chart: { type: "bar", height: 320, stacked: true, fontFamily: "inherit", toolbar: { show: false } },
            colors: [cssVar("--bs-primary"), cssVar("--bs-success")],
            plotOptions: { bar: { borderRadius: 4, columnWidth: "45%" } },
            dataLabels: { enabled: false },
            legend: { show: true, position: "bottom", labels: { colors: labelColor() } },
            xaxis: {
                categories: c.months,
                labels: { style: { colors: labelColor(), fontSize: "11px" }, rotate: -45, rotateAlways: false },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: labelColor() }, formatter: fmtCompact } },
            grid: { borderColor: borderColor(), strokeDashArray: 4 },
            tooltip: { y: { formatter: fmtMoney } },
            noData: { text: "No contributions for this period" }
        });
        charts.contrib.render();
    };

    var renderComparison = function () {
        var el = document.getElementById("bida_chart_comparison");
        if (!el) { return; }
        var c = cfg.charts.comparison;

        charts.comparison = new ApexCharts(el, {
            series: [
                { name: "Contributions", data: c.contributions },
                { name: "Recoveries", data: c.recoveries },
                { name: "Loans Disbursed", data: c.disbursed }
            ],
            chart: { type: "bar", height: 340, fontFamily: "inherit", toolbar: { show: false } },
            colors: [cssVar("--bs-primary"), cssVar("--bs-success"), cssVar("--bs-warning")],
            plotOptions: { bar: { borderRadius: 4, columnWidth: "60%" } },
            dataLabels: { enabled: false },
            legend: { show: true, position: "bottom", labels: { colors: labelColor() } },
            xaxis: {
                categories: c.months || cfg.charts.months,
                labels: { style: { colors: labelColor(), fontSize: "11px" }, rotate: -45, rotateAlways: false },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: labelColor() }, formatter: fmtCompact } },
            grid: { borderColor: borderColor(), strokeDashArray: 4 },
            tooltip: { y: { formatter: fmtMoney } },
            noData: { text: "No activity for this period" }
        });
        charts.comparison.render();
    };

    var renderInterest = function () {
        var el = document.getElementById("bida_chart_interest");
        if (!el) { return; }
        var c = cfg.charts;

        charts.interest = new ApexCharts(el, {
            series: [{ name: "Interest Distributed", data: c.interest }],
            chart: { type: "bar", height: 320, fontFamily: "inherit", toolbar: { show: false } },
            colors: [cssVar("--bs-info")],
            plotOptions: { bar: { borderRadius: 5, columnWidth: "45%" } },
            dataLabels: { enabled: false },
            xaxis: {
                categories: c.months,
                labels: { style: { colors: labelColor(), fontSize: "11px" }, rotate: -45, rotateAlways: false },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: labelColor() }, formatter: fmtCompact } },
            grid: { borderColor: borderColor(), strokeDashArray: 4 },
            tooltip: { y: { formatter: fmtMoney } },
            noData: { text: "No interest distributed this period" }
        });
        charts.interest.render();
    };

    var renderComposition = function () {
        var el = document.getElementById("bida_chart_composition");
        if (!el) { return; }
        var c = cfg.charts.composition;

        charts.composition = new ApexCharts(el, {
            series: c.values,
            labels: c.labels,
            chart: { type: "donut", height: 350, fontFamily: "inherit" },
            colors: palette(),
            stroke: { width: 2, colors: [cssVar("--bs-body-bg")] },
            legend: { position: "bottom", labels: { colors: labelColor() }, fontSize: "12px" },
            dataLabels: {
                enabled: true,
                formatter: function (val) { return Math.round(val) + "%"; },
                style: { fontSize: "11px" }
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true, label: "Total", formatter: function (w) {
                                    return fmtMoney(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0));
                                }
                            }
                        }
                    }
                }
            },
            tooltip: { y: { formatter: fmtMoney } },
            noData: { text: "No credits for this period" }
        });
        charts.composition.render();
    };

    var renderAdvancePortfolio = function () {
        var el = document.getElementById("bida_chart_advance_portfolio");
        if (!el) { return; }
        var c = cfg.charts.advancePortfolio;

        charts.portfolio = new ApexCharts(el, {
            series: c.values,
            labels: c.labels,
            chart: { type: "donut", height: 340, fontFamily: "inherit" },
            colors: palette(),
            stroke: { width: 2, colors: [cssVar("--bs-body-bg")] },
            legend: { position: "bottom", labels: { colors: labelColor() }, fontSize: "12px" },
            dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + "%"; }, style: { fontSize: "11px" } },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true, label: "Advances", formatter: function (w) {
                                    return fmtNumber(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0));
                                }
                            }
                        }
                    }
                }
            },
            tooltip: { y: { formatter: function (v) { return fmtNumber(v) + " advance(s)"; } } },
            noData: { text: "No advances recorded" }
        });
        charts.portfolio.render();
    };

    var renderMembersByGrade = function () {
        var el = document.getElementById("bida_chart_members_grade");
        if (!el) { return; }
        var c = cfg.charts.membersByGrade;

        charts.grade = new ApexCharts(el, {
            series: [{ name: "Members", data: c.values }],
            chart: { type: "bar", height: 320, fontFamily: "inherit", toolbar: { show: false } },
            colors: [cssVar("--bs-info")],
            plotOptions: { bar: { borderRadius: 5, columnWidth: "50%", dataLabels: { position: "top" } } },
            dataLabels: {
                enabled: true, formatter: fmtNumber, offsetY: -18,
                style: { fontSize: "11px", colors: [labelColor()] }
            },
            xaxis: {
                categories: c.labels,
                labels: { style: { colors: labelColor(), fontSize: "11px" } },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: labelColor() }, formatter: function (v) { return fmtNumber(v); } } },
            grid: { borderColor: borderColor(), strokeDashArray: 4 },
            tooltip: { y: { formatter: function (v) { return fmtNumber(v) + " member(s)"; } } },
            noData: { text: "No active members" }
        });
        charts.grade.render();
    };

    /* --------------------------------------------------------------------- */
    /* List (table) views                                                    */
    /* --------------------------------------------------------------------- */

    var sum = function (arr) {
        return (arr || []).reduce(function (a, b) { return a + (b || 0); }, 0);
    };

    // Normalise the two data shapes (initial camelCase config vs snake_case AJAX)
    // into one structure the list builders consume.
    var viewFromConfig = function () {
        var c = cfg.charts;
        return {
            months: c.months, fundGrowth: c.fundGrowth,
            employee: c.employeeContribution, government: c.governmentContribution,
            composition: c.composition, comparison: c.comparison, interest: c.interest
        };
    };
    var viewFromAjax = function (d) {
        return {
            months: d.months, fundGrowth: d.fund_growth,
            employee: d.employee_contribution, government: d.government_contribution,
            composition: d.composition, comparison: d.comparison, interest: d.interest
        };
    };

    // Build a styled table. cols: [{label, cls?}]; rows: [[{v, cls?}, ...]].
    var table = function (cols, rows) {
        var h = '<div class="table-responsive bida-chart-list">' +
            '<table class="table ashik-table align-middle table-row-dashed fs-7 gy-2 mb-0"><thead>' +
            '<tr class="text-muted fw-semibold text-uppercase fs-8">';
        cols.forEach(function (c) { h += '<th class="' + (c.cls || '') + '">' + c.label + '</th>'; });
        h += '</tr></thead><tbody>';
        rows.forEach(function (r) {
            h += '<tr>';
            r.forEach(function (cell) { h += '<td class="' + (cell.cls || '') + '">' + cell.v + '</td>'; });
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        return h;
    };

    var bold = function (s) { return '<span class="fw-bold text-gray-900">' + s + '</span>'; };

    var EMPTY_MSG = "No data for the selected fiscal year";

    // Is a fiscal-year chart effectively empty (no rows, or every value zero)?
    var chartIsEmpty = function (id, v) {
        switch (id) {
            case "bida_chart_fund_growth":
                return !v.fundGrowth || v.fundGrowth.every(function (x) { return !x; });
            case "bida_chart_composition":
                return !v.composition || !v.composition.values || sum(v.composition.values) === 0;
            case "bida_chart_comparison":
                return !v.comparison ||
                    (sum(v.comparison.contributions) + sum(v.comparison.recoveries) + sum(v.comparison.disbursed)) === 0;
            case "bida_chart_contributions":
                return (sum(v.employee) + sum(v.government)) === 0;
            case "bida_chart_interest":
                return sum(v.interest) === 0;
        }
        return false;
    };

    // Show/hide the empty overlay for a chart and hide the (zeroed) canvas behind it.
    var toggleEmpty = function (id, empty) {
        var overlay = document.querySelector('[data-bida-empty="' + id + '"]');
        if (overlay) { overlay.classList.toggle("d-none", !empty); }
        var mount = document.getElementById(id);
        if (mount) { mount.classList.toggle("bida-chart-hidden", empty); }
    };

    var emptyBlock = function (msg) {
        return '<div class="d-flex align-items-center justify-content-center py-15">' +
            '<div class="text-center">' +
            '<i class="ki-outline ki-row-horizontal fs-3x text-gray-300 mb-3"></i>' +
            '<div class="fw-semibold text-gray-500">' + (msg || EMPTY_MSG) + '</div>' +
            '</div></div>';
    };

    var listBuilders = {
        bida_chart_fund_growth: function (v) {
            if (!v.months || !v.fundGrowth) { return ""; }
            var rows = v.months.map(function (m, i) {
                return [{ v: m }, { v: fmtMoney(v.fundGrowth[i]), cls: "text-end fw-semibold text-gray-800" }];
            });
            return table([{ label: "Month" }, { label: "Fund Balance", cls: "text-end" }], rows);
        },
        bida_chart_composition: function (v) {
            var c = v.composition;
            if (!c || !c.labels) { return ""; }
            var total = sum(c.values);
            var rows = c.labels.map(function (l, i) {
                var val = c.values[i];
                var pct = total ? (val / total * 100) : 0;
                return [{ v: l }, { v: fmtMoney(val), cls: "text-end" }, { v: pct.toFixed(1) + "%", cls: "text-end text-muted" }];
            });
            rows.push([{ v: bold("Total") }, { v: bold(fmtMoney(total)), cls: "text-end" }, { v: bold("100%"), cls: "text-end" }]);
            return table([{ label: "Transaction Type" }, { label: "Amount", cls: "text-end" }, { label: "Share", cls: "text-end" }], rows);
        },
        bida_chart_comparison: function (v) {
            var cm = v.comparison;
            if (!cm) { return ""; }
            var rows = v.months.map(function (m, i) {
                return [
                    { v: m },
                    { v: fmtMoney(cm.contributions[i]), cls: "text-end" },
                    { v: fmtMoney(cm.recoveries[i]), cls: "text-end" },
                    { v: fmtMoney(cm.disbursed[i]), cls: "text-end" }
                ];
            });
            rows.push([
                { v: bold("Total") },
                { v: bold(fmtMoney(sum(cm.contributions))), cls: "text-end" },
                { v: bold(fmtMoney(sum(cm.recoveries))), cls: "text-end" },
                { v: bold(fmtMoney(sum(cm.disbursed))), cls: "text-end" }
            ]);
            return table([
                { label: "Month" },
                { label: "Contributions", cls: "text-end" },
                { label: "Recoveries", cls: "text-end" },
                { label: "Loans Disbursed", cls: "text-end" }
            ], rows);
        },
        bida_chart_contributions: function (v) {
            if (!v.employee) { return ""; }
            var rows = v.months.map(function (m, i) {
                var e = v.employee[i], g = v.government[i];
                return [
                    { v: m },
                    { v: fmtMoney(e), cls: "text-end" },
                    { v: fmtMoney(g), cls: "text-end" },
                    { v: fmtMoney(e + g), cls: "text-end fw-semibold" }
                ];
            });
            var te = sum(v.employee), tg = sum(v.government);
            rows.push([
                { v: bold("Total") },
                { v: bold(fmtMoney(te)), cls: "text-end" },
                { v: bold(fmtMoney(tg)), cls: "text-end" },
                { v: bold(fmtMoney(te + tg)), cls: "text-end" }
            ]);
            return table([
                { label: "Month" },
                { label: "Employee", cls: "text-end" },
                { label: "Government", cls: "text-end" },
                { label: "Total", cls: "text-end" }
            ], rows);
        },
        bida_chart_interest: function (v) {
            if (!v.interest) { return ""; }
            var rows = v.months.map(function (m, i) {
                return [{ v: m }, { v: fmtMoney(v.interest[i]), cls: "text-end fw-semibold text-gray-800" }];
            });
            rows.push([{ v: bold("Total") }, { v: bold(fmtMoney(sum(v.interest))), cls: "text-end" }]);
            return table([{ label: "Month" }, { label: "Interest Distributed", cls: "text-end" }], rows);
        }
    };

    var renderLists = function (v) {
        Object.keys(listBuilders).forEach(function (id) {
            var pane = document.getElementById(id + "_list");
            if (!pane) { return; }
            pane.innerHTML = chartIsEmpty(id, v) ? emptyBlock() : listBuilders[id](v);
        });
    };

    // Toggle empty overlays for the five fiscal-year charts from a normalised view.
    var applyChartEmpties = function (v) {
        Object.keys(listBuilders).forEach(function (id) {
            toggleEmpty(id, chartIsEmpty(id, v));
        });
    };

    var bindViewToggles = function () {
        document.querySelectorAll(".bida-view-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var view = this.getAttribute("data-bida-view");
                var group = this.closest(".bida-view-toggle");
                var card = this.closest(".card");
                if (!card) { return; }
                var chartPane = card.querySelector('[data-bida-pane="chart"]');
                var listPane = card.querySelector('[data-bida-pane="list"]');
                if (!chartPane || !listPane) { return; }

                group.querySelectorAll(".bida-view-btn").forEach(function (b) {
                    var active = (b === btn);
                    b.classList.toggle("active", active);
                    b.setAttribute("aria-selected", active ? "true" : "false");
                });

                if (view === "list") {
                    chartPane.classList.add("d-none");
                    listPane.classList.remove("d-none");
                } else {
                    listPane.classList.add("d-none");
                    chartPane.classList.remove("d-none");
                }
            });
        });
    };

    /* --------------------------------------------------------------------- */
    /* Fiscal-year switch (Select2 -> AJAX -> repaint FY charts + FY tiles)  */
    /* --------------------------------------------------------------------- */

    var spinner = function (show) {
        var s = document.querySelector('[data-bida-dashboard="fy-spinner"]');
        if (s) { s.classList.toggle("d-none", !show); }
    };

    var applyFyData = function (d) {
        // Time-series charts.
        if (charts.fund) {
            charts.fund.updateOptions({ xaxis: { categories: d.months } }, false, false);
            charts.fund.updateSeries([{ name: "Fund Balance", data: d.fund_growth }], true);
        }
        if (charts.contrib) {
            charts.contrib.updateOptions({ xaxis: { categories: d.months } }, false, false);
            charts.contrib.updateSeries([
                { name: "Employee", data: d.employee_contribution },
                { name: "Government", data: d.government_contribution }
            ], true);
        }
        if (charts.comparison) {
            charts.comparison.updateOptions({ xaxis: { categories: d.months } }, false, false);
            charts.comparison.updateSeries([
                { name: "Contributions", data: d.comparison.contributions },
                { name: "Recoveries", data: d.comparison.recoveries },
                { name: "Loans Disbursed", data: d.comparison.disbursed }
            ], true);
        }
        if (charts.interest) {
            charts.interest.updateOptions({ xaxis: { categories: d.months } }, false, false);
            charts.interest.updateSeries([{ name: "Interest Distributed", data: d.interest }], true);
        }
        if (charts.composition) {
            charts.composition.updateOptions({ labels: d.composition.labels }, false, false);
            charts.composition.updateSeries(d.composition.values, true);
        }

        // Fiscal-year stat tiles + label.
        if (d.stats) {
            setStat("contributions", d.stats.contributions, true);
            setStat("loans", d.stats.loans_taken_amount, true);
            setStat("recovered", d.stats.recovered, true);
            setStat("interest", d.stats.interest_distributed, true);

            var sub = document.querySelector('[data-bida-substat="loans_count"]');
            if (sub) { sub.textContent = fmtNumber(d.stats.loans_taken_count) + " advances disbursed"; }
        }
        document.querySelectorAll('[data-bida-dashboard="fy-label"]').forEach(function (el) {
            el.textContent = "FY " + d.fiscal_year;
        });

        // Keep the List (table) views and empty states in sync with the new FY.
        var av = viewFromAjax(d);
        renderLists(av);
        applyChartEmpties(av);
    };

    var bindFySwitch = function () {
        // Select2 dispatches change via jQuery only.
        if (typeof window.jQuery === "undefined") { return; }
        var $sel = window.jQuery('[data-bida-dashboard="fy-select"]');
        if (!$sel.length) { return; }

        $sel.on("change", function () {
            var fy = window.jQuery(this).val();
            if (!fy) { return; }
            spinner(true);

            fetch(cfg.chartsUrl + "?fy=" + encodeURIComponent(fy), {
                headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" }
            })
                .then(function (r) { if (!r.ok) { throw new Error("Request failed"); } return r.json(); })
                .then(function (data) { applyFyData(data); })
                .catch(function () {
                    if (window.toastr) { toastr.error("Could not load data for the selected fiscal year."); }
                })
                .finally(function () { spinner(false); });
        });
    };

    /* --------------------------------------------------------------------- */
    /* Public                                                                */
    /* --------------------------------------------------------------------- */

    return {
        init: function (config) {
            cfg = config || {};
            if (!cfg.charts) { return; }

            runCountUps();
            bindFySwitch();

            if (typeof ApexCharts === "undefined") { return; }

            renderFundGrowth();
            renderComposition();
            renderComparison();
            renderAdvancePortfolio();
            renderContributions();
            renderInterest();
            renderMembersByGrade();

            renderLists(viewFromConfig());
            bindViewToggles();

            // Empty states: five FY charts + the two snapshot charts.
            applyChartEmpties(viewFromConfig());
            toggleEmpty("bida_chart_advance_portfolio", sum(cfg.charts.advancePortfolio.values) === 0);
            toggleEmpty("bida_chart_members_grade", sum(cfg.charts.membersByGrade.values) === 0);
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaDashboardConfig !== "undefined") {
        BidaDashboard.init(BidaDashboardConfig);
    }
});