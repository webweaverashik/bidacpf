<?php
namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateMailSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::orderBy('key')->get()->keyBy('key');

        return view('settings.index', compact('settings'));
    }

    public function update(UpdateSettingRequest $request): JsonResponse | RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::set($key, is_array($value) ? json_encode(array_values($value)) : $value);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.',
            ]);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function updateMail(UpdateMailSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $plain = [
            'mailer'            => $data['mailer'],
            'mail_host'         => trim((string) ($data['mail_host'] ?? '')),
            'mail_port'         => $data['mail_port'] ?? '',
            'mail_username'     => trim((string) ($data['mail_username'] ?? '')),
            'mail_encryption'   => $data['mail_encryption'],
            'mail_from_address' => trim((string) $data['mail_from_address']),
            'mail_from_name'    => $data['mail_from_name'],
        ];

        foreach ($plain as $key => $value) {
            Setting::set($key, (string) $value);
        }

        // Only overwrite the password when a new one is supplied; store it encrypted.
        if (filled($data['mail_password'] ?? null)) {
            Setting::set('mail_password', Crypt::encryptString(trim($data['mail_password'])));
        }

        return response()->json([
            'success' => true,
            'message' => 'Mail settings updated successfully.',
        ]);
    }

    public function testMail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_to'           => ['required', 'email'],
            'mailer'            => ['nullable', 'in:smtp,log'],
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'between:1,65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        // Apply the typed values on top of current config for THIS request only, so
        // the admin can verify what they entered before (or after) saving.
        $this->applyMailOverridesForTest($request);

        try {
            Mail::mailer('smtp')->raw(
                'This is a test email from the BIDA CPF system. If you received this, your SMTP settings are working correctly.',
                fn($m) => $m->to($validated['test_to'])->subject('BIDA CPF — SMTP Test')
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test email sent to ' . $validated['test_to'] . '. Please check the inbox.',
        ]);
    }

    /**
     * Push the posted SMTP values into config for the current request and rebuild
     * the smtp transport. A blank password falls back to whatever AppServiceProvider
     * already resolved at boot (the stored/encrypted one, decrypted).
     */
    private function applyMailOverridesForTest(Request $request): void
    {
        $cfg = [];

        if ($request->filled('mail_host')) {
            $cfg['mail.mailers.smtp.host'] = $request->input('mail_host');
        }
        if ($request->filled('mail_port')) {
            $cfg['mail.mailers.smtp.port'] = (int) $request->input('mail_port');
        }

        $cfg['mail.mailers.smtp.username'] = $request->input('mail_username') ?: null;

        if ($request->filled('mail_encryption')) {
            $cfg['mail.mailers.smtp.scheme'] = $request->input('mail_encryption') === 'ssl' ? 'smtps' : null;
        }
        if ($request->filled('mail_from_address')) {
            $cfg['mail.from.address'] = $request->input('mail_from_address');
        }
        if ($request->filled('mail_from_name')) {
            $cfg['mail.from.name'] = $request->input('mail_from_name');
        }
        if ($request->filled('mail_password')) {
            $cfg['mail.mailers.smtp.password'] = trim($request->input('mail_password'));
        }

        config($cfg);
        Mail::purge('smtp'); // force the transport to rebuild with the overrides
    }
}
