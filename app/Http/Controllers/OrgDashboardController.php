<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard organisasi. Untuk lomba memakai login sederhana dengan token per merek.
 */
class OrgDashboardController extends Controller
{
    private const DANGEROUS_PERMISSIONS = [
        'android.permission.READ_SMS', 'android.permission.RECEIVE_SMS', 'android.permission.SEND_SMS',
        'android.permission.SYSTEM_ALERT_WINDOW', 'android.permission.REQUEST_INSTALL_PACKAGES',
        'android.permission.READ_CONTACTS', 'android.permission.BIND_ACCESSIBILITY_SERVICE',
    ];

    public function show(Request $request, Brand $brand): View
    {
        if (! $this->hasAccess($request, $brand)) {
            return view('org.login', ['brand' => $brand]);
        }

        $lookalikes = $brand->lookalikeDomains()
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->latest('first_seen_at')
            ->get();

        $fakeApks = $brand->fakeApks()->latest('last_seen_at')->get();

        return view('org.dashboard', [
            'brand' => $brand,
            'lookalikes' => $lookalikes,
            'fakeApks' => $fakeApks,
            'dangerousPermissions' => self::DANGEROUS_PERMISSIONS,
            'summary' => [
                'lookalikes' => $lookalikes->where('status', 'active')->count(),
                'fake_apks' => $fakeApks->count(),
                'reports_week' => $lookalikes->where('source', 'user')->where('last_checked_at', '>=', now()->subDays(7))->sum('times_reported')
                    + $fakeApks->where('last_seen_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function login(Request $request, Brand $brand): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:200']]);

        if (! $brand->tokenMatches($request->input('token'))) {
            return back()->withErrors(['token' => __('web.org.login_failed')]);
        }

        $request->session()->regenerate();
        $request->session()->put($this->sessionKey($brand), true);

        return redirect()->route('org.show', $brand);
    }

    public function logout(Request $request, Brand $brand): RedirectResponse
    {
        $request->session()->forget($this->sessionKey($brand));

        return redirect()->route('org.show', $brand);
    }

    private function hasAccess(Request $request, Brand $brand): bool
    {
        return $request->session()->get($this->sessionKey($brand)) === true;
    }

    private function sessionKey(Brand $brand): string
    {
        return "org_access.{$brand->id}";
    }
}
