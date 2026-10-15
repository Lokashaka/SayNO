<?php

namespace App\Services\Checks;

use App\Enums\InputType;
use App\Enums\Status;
use App\Models\Check;
use App\Models\OfficialDomain;
use App\Models\Signal;
use App\Services\Analysis\Link\DomainParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Daftar publik domain yang hasil ceknya Bahaya, dikelompokkan per domain.
 * Hanya domain yang ditampilkan; tidak ada data pengirim atau isi pesan.
 */
class PhishingList
{
    public function __construct(private DomainParser $parser) {}

    public function paginate(?string $search, int $perPage = 25): LengthAwarePaginator
    {
        $domain = $this->normalizeSearch($search);

        $page = Check::query()
            ->where('input_type', InputType::Link)
            ->where('status', Status::Danger)
            ->whereNotNull('domain')
            ->whereNotIn('domain', OfficialDomain::query()->select('domain'))
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
        return Check::query()
            ->where('input_type', InputType::Link)
            ->where('status', Status::Danger)
            ->whereNotNull('domain')
            ->whereNotIn('domain', OfficialDomain::query()->select('domain'))
            ->distinct()
            ->count('domain');
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
            'link.lookalike' => __('web.phishing.reasons.lookalike', ['name' => $signal->meta['name'] ?? '']),
            'link.blacklisted' => __('web.phishing.reasons.blacklisted'),
            'link.new_domain' => __('web.phishing.reasons.new_domain'),
            'text.pattern' => __('web.phishing.reasons.pattern', ['category' => __('modus.categories.'.($signal->meta['category'] ?? 'lainnya'))]),
            default => __('web.phishing.reasons.other'),
        };
    }
}
