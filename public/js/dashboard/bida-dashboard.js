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
//   - Only the three time-based charts (fund growth, contributions, ledger
//     composition) repaint on a fiscal-year change; the portfolio and grade
//     charts are point-in-time snapshots.
// =========================================================================
var BidaDashboard = (function () {
    var cfg;
    var charts = {}; // logical name -> ApexCharts instance

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
    /* Stat tile count-up                                                    */
    /* --------------------------------------------------------------------- */

    var runCountUps = function () {
        document.querySelectorAll(".bida-countup").forEach(function (el) {
            var target = parseInt(el.getAttribute("data-bida-value") || "0", 10);
            var money = el.getAttribute("data-bida-money") === "1";
            var duration = 1100;
            var start = null;

            // Respect reduced-motion preferences.
            if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                el.textContent = money ? fmtMoney(target) : fmtNumber(target);
                return;
            }

            var step = function (ts) {
                if (start === null) { start = ts; }
                var p = Math.min((ts - start) / duration, 1);
                var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
                var val = Math.round(target * eased);
                el.textContent = money ? fmtMoney(val) : fmtNumber(val);
                if (p < 1) { requestAnimationFrame(step); }
            };
            requestAnimationFrame(step);
        });
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
                            total: { show: true, label: "Total", formatter: function (w) {
                                return fmtMoney(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0));
                            } }
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
            chart: { type: "donut", height: 320, fontFamily: "inherit" },
            colors: palette(),
            stroke: { width: 2, colors: [cssVar("--bs-body-bg")] },
            legend: { position: "bottom", labels: { colors: labelColor() }, fontSize: "12px" },
            dataLabels: { enabled: true, formatter: function (val) { return Math.round(val) + "%"; }, style: { fontSize: "11px" } },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: { show: true, label: "Advances", formatter: function (w) {
                                return fmtNumber(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0));
                            } }
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
    /* Fiscal-year switch (Select2 -> AJAX -> repaint FY-scoped charts)      */
    /* --------------------------------------------------------------------- */

    var spinner = function (show) {
        var s = document.querySelector('[data-bida-dashboard="fy-spinner"]');
        if (s) { s.classList.toggle("d-none", !show); }
    };

    var applyFyData = function (d) {
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
        if (charts.composition) {
            charts.composition.updateOptions({ labels: d.composition.labels }, false, false);
            charts.composition.updateSeries(d.composition.values, true);
        }
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
            renderContributions();
            renderComposition();
            renderAdvancePortfolio();
            renderMembersByGrade();
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    if (typeof BidaDashboardConfig !== "undefined") {
        BidaDashboard.init(BidaDashboardConfig);
    }
});
