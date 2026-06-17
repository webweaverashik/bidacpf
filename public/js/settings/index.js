"use strict";

var BidaCpfSetting = (function () {
      var form;
      var submitButton;
      var config = window.BidaCpfSettingConfig || {};

      function clearErrors() {
            form.querySelectorAll(".fv-feedback").forEach(function (el) {
                  el.textContent = "";
            });
      }

      function showError(field, message) {
            var input = form.querySelector('[name="' + field + '"]');
            if (!input) return;

            var row = input.closest(".fv-row");
            if (!row) return;

            var feedback = row.querySelector(".fv-feedback");
            if (feedback) feedback.textContent = message;
      }

      function collectSettings() {
            var settings = {};

            form.querySelectorAll("input[name], select[name]").forEach(function (el) {
                  var name = el.getAttribute("name");
                  if (!name || name === "_token") return;

                  settings[name] = el.type === "checkbox" ? (el.checked ? "1" : "0") : el.value;
            });

            return settings;
      }

      function setLoading(state) {
            if (state) {
                  submitButton.setAttribute("data-kt-indicator", "on");
                  submitButton.disabled = true;
            } else {
                  submitButton.removeAttribute("data-kt-indicator");
                  submitButton.disabled = false;
            }
      }

      function save() {
            clearErrors();
            setLoading(true);

            fetch(config.updateUrl, {
                  method: "PUT",
                  headers: {
                        "X-CSRF-TOKEN": config.csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                  },
                  credentials: "same-origin",
                  body: JSON.stringify({ settings: collectSettings() }),
            })
                  .then(function (response) {
                        return response.json().then(function (body) {
                              return { ok: response.ok, status: response.status, body: body };
                        });
                  })
                  .then(function (result) {
                        if (result.ok) {
                              toastr.success(result.body.message || "Settings updated successfully.");
                              return;
                        }

                        if (result.status === 422 && result.body.errors) {
                              Object.keys(result.body.errors).forEach(function (key) {
                                    showError(key.replace(/^settings\./, ""), result.body.errors[key][0]);
                              });
                              toastr.error("Please correct the highlighted fields.");
                              return;
                        }

                        toastr.error(result.body.message || "Could not save settings. Please try again.");
                  })
                  .catch(function () {
                        toastr.error("A network error occurred. Please try again.");
                  })
                  .finally(function () {
                        setLoading(false);
                  });
      }

      return {
            init: function () {
                  form = document.getElementById("kt_settings_form");
                  submitButton = document.getElementById("btn_save_settings");

                  if (!form || !submitButton) return;

                  submitButton.addEventListener("click", save);
            },
      };
})();

KTUtil.onDOMContentLoaded(function () {
      BidaCpfSetting.init();
});