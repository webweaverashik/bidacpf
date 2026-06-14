<!--begin::Modal - Edit User-->
<div class="modal fade" id="kt_modal_edit_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_user_header">
                <h2 class="fw-bold" id="kt_modal_edit_user_title">Update User</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-edit-users-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_user_form" class="form" action="#" novalidate="novalidate">
                    <input type="hidden" name="user_id" id="edit_user_id" />

                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_user_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_edit_user_header"
                        data-kt-scroll-wrappers="#kt_modal_edit_user_scroll" data-kt-scroll-offset="300px">

                        <!--begin::Photo-->
                        <div class="fv-row mb-7 text-center">
                            <label class="d-block fw-semibold fs-6 mb-3">User Photo</label>
                            <div class="image-input image-input-outline image-input-placeholder d-inline-block"
                                data-kt-image-input="true" id="kt_image_input_edit">
                                <div class="image-input-wrapper w-125px h-125px"
                                    style="background-image: url('{{ asset('img/male-placeholder.png') }}');"></div>
                                <label
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change photo">
                                    <i class="ki-outline ki-pencil fs-7"></i>
                                    <input type="file" name="photo" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="remove_photo" value="0" />
                                </label>
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel photo">
                                    <i class="ki-outline ki-cross fs-2"></i>
                                </span>
                                <span
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove photo">
                                    <i class="ki-outline ki-cross fs-2"></i>
                                </span>
                            </div>
                            <div class="form-text">Allowed: png, jpg, jpeg. Max size: 100KB</div>
                        </div>
                        <!--end::Photo-->

                        <div class="row">
                            <!--begin::Role-->
                            <div class="col-lg-12 d-none">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Role</label>
                                    <div class="row g-3">
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="Admin"
                                                id="edit_role_admin" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="edit_role_admin">
                                                <i class="las la-user-secret fs-2x me-3"></i>
                                                <span class="text-gray-900 fw-bold fs-6">Admin</span>
                                            </label>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="CPF Officer"
                                                id="edit_role_officer" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="edit_role_officer">
                                                <i class="las la-user-ninja fs-2x me-3"></i>
                                                <span class="text-gray-900 fw-bold fs-6">CPF Officer</span>
                                            </label>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="Auditor"
                                                id="edit_role_auditor" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="edit_role_auditor">
                                                <i class="las la-user fs-2x me-3"></i>
                                                <span class="text-gray-900 fw-bold fs-6">Auditor</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end::Role-->

                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Name</label>
                                    <input type="text" name="name" class="form-control form-control-solid"
                                        placeholder="Write full name" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Designation</label>
                                    <input type="text" name="designation" class="form-control form-control-solid"
                                        placeholder="e.g. Assistant Director" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Email</label>
                                    <input type="email" name="email" class="form-control form-control-solid"
                                        placeholder="name@bida.gov.bd" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Mobile No.</label>
                                    <input type="text" name="mobile_number"
                                        class="form-control form-control-solid" placeholder="e.g. 01812345678"
                                        maxlength="11" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="notice d-flex align-items-center bg-light-primary rounded border-primary border border-dashed p-4">
                            <i class="ki-outline ki-information-5 fs-2tx text-primary me-3"></i>

                            <div class="fw-semibold fs-7 text-gray-700">
                                To change this user's password, use the <strong>Reset Password</strong> action on the
                                list.
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3"
                            data-edit-users-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-edit-users-modal-action="submit">
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
<!--end::Modal - Edit User-->
