<div class="card card-flush h-100">
    <div class="card-header pt-5">
        <div class="card-title fs-4 fw-bold text-gray-900">Recent Transactions</div>
        @if (Route::has('cpf-ledger.transactions'))
            <div class="card-toolbar">
                <a href="{{ route('cpf-ledger.transactions') }}" class="btn btn-sm btn-light">View all</a>
            </div>
        @endif
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table ashik-table align-middle table-row-dashed fs-7 gy-3 mb-0">
                <thead>
                    <tr class="text-muted fw-semibold text-uppercase fs-8">
                        <th>Date</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $t)
                        @php $isCredit = (int) $t->credit > 0; @endphp
                        <tr>
                            <td class="text-nowrap text-gray-700">{{ $t->transaction_date->format('d M Y') }}</td>
                            <td>
                                <span class="fw-semibold text-gray-800 d-block">{{ $t->employee?->name ?? '—' }}</span>
                                <span class="text-muted fs-8">{{ $t->employee?->cpf_account_no }}</span>
                            </td>
                            <td><span class="badge badge-light fs-8">{{ $t->transaction_type->label() }}</span></td>
                            <td class="text-end fw-bold {{ $isCredit ? 'text-success' : 'text-danger' }} text-nowrap">
                                {{ $isCredit ? '+' : '−' }} ৳
                                {{ number_format((int) ($isCredit ? $t->credit : $t->debit)) }}
                            </td>
                            <td class="text-end fw-semibold text-gray-700 text-nowrap">৳
                                {{ number_format((int) $t->balance) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-10">No transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
