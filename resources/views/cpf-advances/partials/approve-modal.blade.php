<div class="modal fade" id="adv_approve_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('cpf-advances.approve', $advance) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h3 class="modal-title">Approve Advance</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">You may adjust the figures below before approving. On approval the disbursement is posted to the CPF ledger.</p>
                    <div class="mb-5">
                        <label class="required form-label">Approved Amount (BDT)</label>
                        <input type="number" name="approved_amount" class="form-control" min="1" step="1"
                            value="{{ $advance->requested_amount }}" required />
                    </div>
                    <div class="row g-5">
                        <div class="col-6">
                            <label class="required form-label">Interest Rate (%)</label>
                            <input type="number" name="interest_rate" class="form-control" min="0" max="100" step="0.01"
                                value="{{ $advance->interest_rate }}" required />
                        </div>
                        <div class="col-6">
                            <label class="required form-label">Installments</label>
                            <input type="number" name="installment_count" class="form-control" min="1" step="1"
                                value="{{ $advance->installment_count }}" required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve & Disburse</button>
                </div>
            </form>
        </div>
    </div>
</div>
