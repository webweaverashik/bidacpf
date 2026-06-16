<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

// Cross-cutting: serves polymorphic attachments for every module.
Route::get('attachments/{attachment}/{name?}', [AttachmentController::class, 'show'])
    ->name('attachments.show');
