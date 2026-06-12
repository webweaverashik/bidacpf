<!--begin::Modal - Reset User Password-->
<div class="modal fade" id="kt_modal_edit_password" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_password_header">
                <h2 class="fw-bold" id="kt_modal_edit_password_title">Password Reset</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-password-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_password_form" class="form" action="#" novalidate="novalidate">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="userPasswordNew" class="form-control"
                                placeholder="Enter new password" autocomplete="new-password" />
                            <span class="input-group-text toggle-password" data-target="userPasswordNew"
                                style="cursor: pointer;" title="See Password" data-bs-toggle="tooltip">
                                <i class="ki-outline ki-eye fs-3"></i>
                            </span>
                        </div>
                        <div id="password-strength-text" class="mt-1 fw-bold small text-muted"></div>
                        <div class="progress mt-1" style="height: 5px;">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%">
                            </div>
                        </div>
                        <div class="text-muted fs-8 mt-2">
                            Min 8 chars, with uppercase, lowercase, number &amp; special character.
                        </div>
                    </div>

                    <div class="text-center pt-5">
                        <button type="reset" class="btn btn-light me-3"
                            data-kt-edit-password-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-success" data-kt-edit-password-modal-action="submit">
                            <span class="indicator-label">Update</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Reset User Password-->
