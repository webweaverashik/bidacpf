<div class="modal fade" id="adv_reschedule_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('cpf-advances.reschedule', $advance) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h3 class="modal-title">Reschedule Repayment</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Recalculates the per-installment amount from the current outstanding balance of
                        <span class="fw-bold">{{ number_format($advance->outstanding_amount) }}</span>.
                    </p>
                    <div class="mb-5">
                        <label class="required form-label">New Installment Count</label>
                        <input id="resched_installments" type="number" name="installment_count" class="form-control"
                            min="1" step="1" value="{{ $advance->installment_count }}"
                            data-outstanding="{{ $advance->outstanding_amount }}" required />
                    </div>
                    <div class="alert alert-light-primary mb-0">
                        New per-installment: <span class="fw-bold">BDT <span id="resched_preview">—</span></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Recalculate</button>
                </div>
            </form>
        </div>
    </div>
</div>
