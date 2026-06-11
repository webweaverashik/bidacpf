<?php

use App\Http\Controllers\Cpf\CpfLedgerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CPF Ledger Routes
|--------------------------------------------------------------------------
|
| Three read-only views, each with a server-side DataTable feed and an
| export endpoint:
|   - Members     : per-member balance listing
|   - Transactions: all ledger movements
|   - Statement   : a single employee's running statement
|
| All gated by cpf_ledger.view.
|
| Loaded by routes/modules.php inside the ['auth', 'isLoggedIn'] group.
|
| Ordering: the fixed `transactions` paths are declared before the
| `cpf-ledger/{employee}` statement wildcard so they are not captured by it.
*/

Route::middleware('can:cpf_ledger.view')->group(function () {
    // Members
    Route::get('cpf-ledger', [CpfLedgerController::class, 'index'])->name('cpf-ledger.index');
    Route::get('cpf-ledger/data', [CpfLedgerController::class, 'indexData'])->name('cpf-ledger.data');
    Route::get('cpf-ledger/export', [CpfLedgerController::class, 'export'])->name('cpf-ledger.export');

    // Transactions
    Route::get('cpf-ledger/transactions', [CpfLedgerController::class, 'transactions'])->name('cpf-ledger.transactions');
    Route::get('cpf-ledger/transactions/data', [CpfLedgerController::class, 'transactionsData'])->name('cpf-ledger.transactions.data');
    Route::get('cpf-ledger/transactions/export', [CpfLedgerController::class, 'transactionsExport'])->name('cpf-ledger.transactions.export');

    // Statement (per employee) — wildcard registered LAST
    Route::get('cpf-ledger/{employee}', [CpfLedgerController::class, 'show'])->name('cpf-ledger.show');
    Route::get('cpf-ledger/{employee}/data', [CpfLedgerController::class, 'statementData'])->name('cpf-ledger.statement.data');
    Route::get('cpf-ledger/{employee}/export', [CpfLedgerController::class, 'statementExport'])->name('cpf-ledger.statement.export');
});
