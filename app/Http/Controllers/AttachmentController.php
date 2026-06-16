<?php
namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Cpf\CpfFinalSettlement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * Maps each attachable type to the permission required to view it.
     * Mirrors the can: gates used on the owning module's show route.
     */
    private const VIEW_PERMISSIONS = [
        CpfAdvance::class         => 'cpf_advance.view',
        CpfAdvanceRecovery::class => 'cpf_advance.view',
        CpfFinalSettlement::class => 'cpf_settlement.view',
    ];

    private const INLINE_TYPES = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];


    public function show(Request $request, Attachment $attachment, ?string $name = null)
    {
        $permission = self::VIEW_PERMISSIONS[$attachment->attachable_type] ?? null;
        abort_if($permission === null, 403);
        abort_unless($request->user()->can($permission), 403);

        $absolute = storage_path('app/private/' . ltrim($attachment->file_path, '/'));
        abort_unless(is_file($absolute), 404);

        $headers = [
            'Content-Type'           => $attachment->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $forceDownload = $request->boolean('download')
            || ! in_array($attachment->mime_type, self::INLINE_TYPES, true);

        if ($forceDownload) {
            // Content-Disposition: attachment; filename="<file_name>"
            return response()->download($absolute, $attachment->file_name, $headers);
        }

        $response = response()->file($absolute, $headers);

        // Inline, but make the save-as / title default to the original name.
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->file_name,
            Str::ascii($attachment->file_name) // ASCII fallback for non-UTF-8 clients
        ));

        return $response;
    }
}
