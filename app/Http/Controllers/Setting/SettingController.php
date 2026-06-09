<?php
namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
}
