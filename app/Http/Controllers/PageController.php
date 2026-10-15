<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Check;
use App\Models\LookalikeDomain;
use App\Services\Checks\PhishingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $stats = Cache::remember('home-stats', now()->addMinutes(5), fn () => [
            'total' => Check::count(),
            'danger_week' => Check::where('status', Status::Danger)->where('created_at', '>=', now()->subDays(7))->count(),
            'lookalikes' => LookalikeDomain::where('status', 'active')->count(),
        ]);

        return view('home', [
            'stats' => $stats,
            'modus' => array_slice(__('modus.items'), 0, 3),
            'whatsappUrl' => $this->whatsappUrl(),
        ]);
    }

    public function phishing(Request $request, PhishingList $list): View
    {
        $search = $list->normalizeSearch($request->query('q'));

        return view('phishing', [
            'items' => $list->paginate($search),
            'total' => $list->count(),
            'search' => $search,
            'whatsappUrl' => $this->whatsappUrl(),
        ]);
    }

    public function modus(): View
    {
        return view('modus', ['items' => __('modus.items')]);
    }

    public function privacy(): View
    {
        return view('legal', ['key' => 'privacy', 'title' => __('web.nav.privacy_policy')]);
    }

    public function terms(): View
    {
        return view('legal', ['key' => 'terms', 'title' => __('web.nav.terms')]);
    }

    private function whatsappUrl(): ?string
    {
        $number = preg_replace('/\D/', '', (string) config('sayno.whatsapp_number'));

        return $number !== '' ? 'https://wa.me/'.$number.'?text='.rawurlencode(__('web.whatsapp_greeting')) : null;
    }
}
