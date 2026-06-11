{{-- Live schedule preview. IDs are driven by bida-advance-form.js (updateCalc). --}}
<div id="adv_calc" class="alert alert-light-primary border border-primary border-dashed d-none mt-2 mb-0">
    <div class="d-flex flex-wrap gap-7">
        <div>
            <div class="text-muted fs-7 text-uppercase">Principal</div>
            <div class="fs-4 fw-bold text-gray-900">BDT <span id="adv_calc_principal">0</span></div>
        </div>
        <div>
            <div class="text-muted fs-7 text-uppercase">Interest (<span id="adv_calc_rate">0</span>%)</div>
            <div class="fs-4 fw-bold text-success">BDT <span id="adv_calc_interest">0</span></div>
        </div>
        <div>
            <div class="text-muted fs-7 text-uppercase">Total Repayable</div>
            <div class="fs-4 fw-bold text-primary">BDT <span id="adv_calc_total">0</span></div>
        </div>
        <div>
            <div class="text-muted fs-7 text-uppercase">Per Installment</div>
            <div class="fs-4 fw-bold text-gray-900">BDT <span id="adv_calc_installment">0</span></div>
        </div>
        <div id="adv_calc_last_wrap" class="d-none">
            <div class="text-muted fs-7 text-uppercase">Final Installment</div>
            <div class="fs-4 fw-bold text-gray-900">BDT <span id="adv_calc_last">0</span></div>
        </div>
    </div>
    <div class="text-muted fs-8 mt-2">
        Total repayable = principal + interest, spread over the installments. Each installment clears the
        principal first; once the principal is cleared the remainder of each installment reduces the interest.
        The interest the member repays is credited back to their CPF account.
    </div>
</div>
