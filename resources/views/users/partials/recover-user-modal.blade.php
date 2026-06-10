<!--begin::Modal - Recover User-->
<div class="modal fade" id="kt_modal_recover_user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-400px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold text-success">
                    <i class="ki-outline ki-arrow-circle-left fs-2 text-success me-2"></i>Recover User
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-5">
                    <span class="badge badge-light-success fs-5 px-4 py-3" id="recover_user_name">User Name</span>
                </div>
                <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-5">
                    <i class="ki-outline ki-shield-tick fs-2tx text-success me-4"></i>
                    <div class="fw-semibold">
                        <p class="text-gray-700 fs-6 mb-0">
                            This restores the account and allows the user to log in again.
                            All previous data will be accessible.
                        </p>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btn_recover_confirm">
                        <span class="indicator-label"><i class="ki-outline ki-arrow-circle-left fs-4 me-1"></i>Recover
                            User</span>
                        <span class="indicator-progress">Recovering...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Recover User-->
