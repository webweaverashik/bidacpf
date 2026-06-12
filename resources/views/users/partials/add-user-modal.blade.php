<!--begin::Modal - Add User-->
<div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_user_header">
                <h2 class="fw-bold">Add New User</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-add-users-modal-action="close">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_user_form" class="form" action="#" novalidate="novalidate">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_add_user_header"
                        data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">

                        <!--begin::Photo-->
                        <div class="fv-row mb-7 text-center">
                            <label class="d-block fw-semibold fs-6 mb-3">User Photo</label>
                            <div class="image-input image-input-outline image-input-placeholder d-inline-block"
                                data-kt-image-input="true" id="kt_image_input_add">
                                <div class="image-input-wrapper w-125px h-125px"
                                    style="background-image: url('{{ asset('img/male-placeholder.png') }}');"></div>
                                <label
                                    class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change photo">
                                    <i class="ki-outline ki-pencil fs-7"></i>
                                    <input type="file" name="photo" accept=".png, .jpg, .jpeg" />
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
                            <div class="col-lg-12">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Role</label>
                                    <div class="row g-3">
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="Admin"
                                                id="add_role_admin" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="add_role_admin">
                                                <i class="las la-user-secret fs-2x me-3"></i>
                                                <span class="text-gray-900 fw-bold fs-6">Admin</span>
                                            </label>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="CPF Officer"
                                                id="add_role_officer" checked="checked" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="add_role_officer">
                                                <i class="las la-user-ninja fs-2x me-3"></i>
                                                <span class="text-gray-900 fw-bold fs-6">CPF Officer</span>
                                            </label>
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="radio" class="btn-check" name="role" value="Auditor"
                                                id="add_role_auditor" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="add_role_auditor">
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
                                    <input type="text" name="name" class="form-control"
                                        placeholder="Write full name" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Designation</label>
                                    <input type="text" name="designation" class="form-control"
                                        placeholder="e.g. Assistant Director" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Email</label>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="name@bida.gov.bd" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Mobile No.</label>
                                    <input type="text" name="mobile_number"
                                        class="form-control" placeholder="e.g. 01812345678"
                                        maxlength="11" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="add_password"
                                            class="form-control" placeholder="Enter password"
                                            autocomplete="new-password" />
                                        <span class="input-group-text toggle-password" data-target="add_password"
                                            style="cursor:pointer;">
                                            <i class="ki-outline ki-eye fs-3"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                                    <input type="password" name="password_confirmation"
                                        class="form-control" placeholder="Re-enter password"
                                        autocomplete="new-password" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3"
                            data-add-users-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-add-users-modal-action="submit">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Add User-->
