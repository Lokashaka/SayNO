<?php

namespace App\Services\Checks;

use App\Enums\InputType;
use App\Enums\Status;
use App\Models\Check;
use App\Models\LookalikeDomain;
use App\Models\OfficialDomain;
use App\Models\Signal;
use App\Services\Analysis\Link\DomainParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Daftar publik domain berbahaya, dalam dua bagian:
 * - domain yang hasil ceknya Bahaya, dikelompokkan per domain;
 * - domain tiruan merek yang sudah terdaftar, hasil pemindaian sayno:scan-lookalikes.
 * Hanya domain yang ditampilkan; tidak ada data pengirim atau isi pesan.
 */
class PhishingList
{
    public function __construct(private DomainParser $parser) {}

    public function paginate(?string $search, int $perPage = 25): LengthAwarePaginator
    {
        $domain = $this->normalizeSearch($search);

        $page = $this->dangerousChecks()
            ->when($domain, fn ($query) => $query->where('domain', 'like', '%'.addcslashes($domain, '%_\\').'%'))
            ->selectRaw('domain, count(*) as reports, min(created_at) as first_seen, max(created_at) as last_seen, max(id) as latest_id')
            ->groupBy('domain')
            ->orderByDesc('last_seen')
            ->paginate($perPage)
            ->withQueryString();

        // ULID berurutan menurut waktu, jadi max(id) adalah pemeriksaan terakhir per domain.
        $signals = Signal::query()
            ->whereIn('check_id', $page->pluck('latest_id'))
            ->where('weight', '>', 0)
            ->orderByDesc('weight')
            ->get()
            ->groupBy('check_id');

        $page->getCollection()->transform(fn ($row) => [
            'domain' => $row->domain,
            'defanged' => str_replace('.', '[.]', $row->domain),
            'reports' => (int) $row->reports,
            'first_seen' => Carbon::parse($row->first_seen),
            'last_seen' => Carbon::parse($row->last_seen),
            'reason' => $this->reason($signals->get($row->latest_id)?->first()),
        ]);

        return $page;
    }

    public function count(): int
    {
        return $this->dangerousChecks()->distinct()->count('domain');
    }

    /**
     * Domain tiruan yang masih terdaftar dan dinilai meniru atau belum ditinjau. Domain yang
     * diparkir atau milik pihak lain tidak ditampilkan, dan yang sudah ada di bagian Bahaya tidak diulang.
     */
    public function lookalikes(?string $search, int $perPage = 25): LengthAwarePaginator
    {
        $page = $this->activeLookalikes($this->normalizeSearch($search))
            ->with('brand:id,name')
            ->orderByRaw('case when verdict = ? then 0 else 1 end', [LookalikeDomain::SUSPICIOUS])
            ->latest('first_seen_at')
            ->orderBy('domain')
            ->paginate($perPage, pageName: 'tiruan')
            ->withQueryString()
            ->fragment('tiruan');

        $page->getCollection()->transform(fn (LookalikeDomain $lookalike) => [
            'domain' => $lookalike->domain,
            'defanged' => str_replace('.', '[.]', $lookalike->domain),
            'brand' => $lookalike->brand->name,
            'technique' => __('signals.techniques.'.$lookalike->technique),
            'suspicious' => $lookalike->verdict === LookalikeDomain::SUSPICIOUS,
            'registered_at' => $lookalike->registered_at,
            'first_seen' => $lookalike->first_seen_at,
        ]);

        return $page;
    }

    public function lookalikeCount(): int
    {
        return $this->activeLookalikes(null)->distinct()->count('domain');
    }

    private function dangerousChecks(): Builder
    {
        return Check::query()
            ->where('input_type', InputType::Link)
            ->where('status', Status::Danger)
            ->whereNotNull('domain')
            ->whereNotIn('domain', OfficialDomain::query()->select('domain'));
    }

    private function activeLookalikes(?string $domain): Builder
    {
        return LookalikeDomain::query()
            ->listed()
            ->whereNotIn('domain', OfficialDomain::query()->select('domain'))
            ->whereNotIn('domain', $this->dangerousChecks()->select('domain'))
            ->when($domain, fn ($query) => $query->where('domain', 'like', '%'.addcslashes($domain, '%_\\').'%'));
    }

    public function normalizeSearch(?string $search): ?string
    {
        $search = trim((string) $search);

        if ($search === '') {
            return null;
        }

        return $this->parser->host($search) ?? mb_strtolower(mb_substr($search, 0, 100));
    }

    private function reason(?Signal $signal): string
    {
        if ($signal === null) {
            return __('web.phishing.reasons.other');
        }

        return match ($signal->code) {
            'link.lookalike', 'link.known_lookalike' => __('web.phishing.reasons.lookalike', ['name' => $signal->meta['name'] ?? '']),
            'link.blacklisted' => __('web.phishing.reasons.blacklisted'),
            'link.new_domain' => __('web.phishing.reasons.new_domain'),
            'text.pattern' => __('web.phishing.reasons.pattern', ['category' => __('modus.categories.'.($signal->meta['category'] ?? 'lainnya'))]),
            default => __('web.phishing.reasons.other'),
        };
    }
}
