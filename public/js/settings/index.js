"use strict";

var BidaCpfSetting = (function () {

      // ── Private state ─────────────────────────────────────────────────────
      var config = window.BidaCpfSettingConfig || {};
      var form;
      var submitButton;

      var numericFields = [
            { name: "employee_contribution_rate", label: "Employee contribution rate", min: 0, max: 100, integer: false },
            { name: "government_contribution_rate", label: "Government contribution rate", min: 0, max: 100, integer: false },
            { name: "advance_limit_percentage", label: "Advance limit", min: 0, max: 100, integer: false },
            { name: "advance_interest_rate", label: "Advance interest rate", min: 0, max: 100, integer: false },
            { name: "max_installments", label: "Maximum installments", min: 1, max: 120, integer: true },
      ];

      // ── Error helpers ─────────────────────────────────────────────────────
      var feedbackFor = function (input) {
            return input.closest(".fv-row").querySelector(".fv-feedback");
      };

      var setError = function (input, message) {
            input.classList.add("is-invalid");
            var fb = feedbackFor(input);
            fb.textContent = message;
            fb.classList.add("show");
      };

      var clearError = function (input) {
            input.classList.remove("is-invalid");
            var fb = feedbackFor(input);
            fb.textContent = "";
            fb.classList.remove("show");
      };

      // ── Validation ────────────────────────────────────────────────────────
      var validateNumeric = function (cfg) {
            var input = form.querySelector('[name="' + cfg.name + '"]');
            var raw = input.value.trim();

            if (raw === "") {
                  setError(input, cfg.label + " is required");
                  return false;
            }
            var value = Number(raw);
            if (isNaN(value)) {
                  setError(input, cfg.label + " must be a number");
                  return false;
            }
            if (cfg.integer && !Number.isInteger(value)) {
                  setError(input, cfg.label + " must be a whole number");
                  return false;
            }
            if (value < cfg.min || value > cfg.max) {
                  setError(input, cfg.label + " must be between " + cfg.min + " and " + cfg.max);
                  return false;
            }
            clearError(input);
            return true;
      };

      var validateAll = function () {
            var valid = true;

            numericFields.forEach(function (cfg) {
                  if (!validateNumeric(cfg)) {
                        valid = false;
                  }
            });

            return valid;
      };

      // Map Laravel 422 errors back onto fields
      var applyServerErrors = function (errors) {
            Object.keys(errors).forEach(function (key) {
                  var field = key.replace("settings.", "").split(".")[0];
                  var message = Array.isArray(errors[key]) ? errors[key][0] : errors[key];

                  var input = form.querySelector('[name="' + field + '"]');
                  if (input) {
                        setError(input, message);
                  }
            });
      };

      // ── Submit ────────────────────────────────────────────────────────────
      var buildPayload = function () {
            var payload = { settings: {} };

            numericFields.forEach(function (cfg) {
                  payload.settings[cfg.name] = form.querySelector('[name="' + cfg.name + '"]').value.trim();
            });

            return payload;
      };

      var setLoading = function (state) {
            if (state) {
                  submitButton.setAttribute("data-kt-indicator", "on");
                  submitButton.disabled = true;
            } else {
                  submitButton.removeAttribute("data-kt-indicator");
                  submitButton.disabled = false;
            }
      };

      var handleSubmit = function () {
            if (!validateAll()) {
                  toastr.error("Please correct the highlighted fields.");
                  return;
            }

            setLoading(true);

            fetch(config.updateUrl, {
                  method: "PUT",
                  headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": config.csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                  },
                  body: JSON.stringify(buildPayload()),
            })
                  .then(function (response) {
                        return response.json().catch(function () { return {}; })
                              .then(function (data) { return { ok: response.ok, status: response.status, data: data }; });
                  })
                  .then(function (result) {
                        if (result.ok) {
                              toastr.success(result.data.message || "Settings updated successfully.");
                        } else if (result.status === 422 && result.data.errors) {
                              applyServerErrors(result.data.errors);
                              toastr.error("Please correct the highlighted fields.");
                        } else {
                              toastr.error(result.data.message || "Something went wrong. Please try again.");
                        }
                  })
                  .catch(function () {
                        toastr.error("Network error. Please try again.");
                  })
                  .finally(function () {
                        setLoading(false);
                  });
      };

      // ── Event binding ─────────────────────────────────────────────────────
      var bindEvents = function () {
            submitButton.addEventListener("click", handleSubmit);

            numericFields.forEach(function (cfg) {
                  form.querySelector('[name="' + cfg.name + '"]')
                        .addEventListener("input", function () { clearError(this); });
            });
      };

      // ── Public API ────────────────────────────────────────────────────────
      return {
            init: function () {
                  form = document.getElementById("kt_settings_form");
                  submitButton = document.getElementById("btn_save_settings");

                  if (!form || !submitButton) {
                        return;
                  }

                  bindEvents();
            }
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaCpfSetting.init();
});