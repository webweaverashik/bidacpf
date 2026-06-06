<?php
namespace App\Http\Controllers\Setting;

use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::orderBy('key')->get()->keyBy('key');

        return view('settings.index', compact('settings'));
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
