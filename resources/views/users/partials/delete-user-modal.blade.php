<!--begin::Modal - Delete User-->
<div class="modal fade" id="kt_modal_delete_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold text-danger">
                    <i class="ki-outline ki-shield fs-2 text-danger me-2"></i>Delete User Account
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body pt-0">
                <!--begin::Step 1-->
                <div id="delete_step_warning">
                    <div class="d-flex align-items-center bg-light-primary rounded p-4 mb-5">
                        <div class="symbol symbol-50px me-4">
                            <img id="delete_user_photo" src="{{ asset('img/male-placeholder.png') }}" alt="User" />
                        </div>
                        <div>
                            <div class="fw-bold text-gray-900 fs-5" id="delete_user_name">User Name</div>
                            <div class="text-muted fs-7" id="delete_user_email">user@email.com</div>
                        </div>
                    </div>

                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-5">
                        <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold">What happens when you delete this user?</h4>
                            <ul class="text-gray-700 fs-6 mb-0 ps-4">
                                <li>Login access is revoked immediately.</li>
                                <li>The account is hidden from the active users list.</li>
                                <li>Their CPF records and activity history remain intact.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-5">
                        <i class="ki-outline ki-shield-tick fs-2tx text-success me-4"></i>
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold">Data is Safe!</h4>
                            <p class="text-gray-700 fs-6 mb-0">
                                This is a soft delete. An administrator can recover the account using
                                <strong>Show Deleted Only</strong> at any time.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, Keep User</button>
                        <button type="button" class="btn btn-danger" id="btn_delete_proceed">
                            <i class="ki-outline ki-trash fs-4 me-1"></i>Yes, Delete User
                        </button>
                    </div>
                </div>
                <!--end::Step 1-->

                <!--begin::Step 2-->
                <div id="delete_step_confirm" style="display: none;">
                    <div class="text-center mb-5">
                        <span class="badge badge-light-danger fs-5 px-4 py-3" id="delete_user_name_confirm">User
                            Name</span>
                    </div>
                    <div class="alert alert-danger d-flex align-items-center p-4 mb-5">
                        <i class="ki-outline ki-shield-cross fs-2hx text-danger me-3"></i>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-5">Final Confirmation Required</span>
                            <span class="text-gray-700">Type <strong>DELETE</strong> below to confirm.</span>
                        </div>
                    </div>
                    <div class="fv-row mb-5">
                        <input type="text" id="delete_confirm_input" class="form-control form-control-lg text-center"
                            placeholder="Type DELETE to confirm" autocomplete="off" />
                    </div>
                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" class="btn btn-light" id="btn_delete_back">
                            <i class="ki-outline ki-arrow-left fs-4 me-1"></i>Go Back
                        </button>
                        <button type="button" class="btn btn-danger" id="btn_delete_confirm" disabled>
                            <span class="indicator-label"><i class="ki-outline ki-trash fs-4 me-1"></i>Delete
                                User</span>
                            <span class="indicator-progress">Deleting...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
                <!--end::Step 2-->
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Delete User-->
