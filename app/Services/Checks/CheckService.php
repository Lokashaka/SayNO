<?php

namespace App\Services\Checks;

use App\Enums\InputType;
use App\Models\Check;
use App\Models\FakeApk;
use App\Models\LookalikeDomain;
use App\Services\Analysis\ApkAnalyzer;
use App\Services\Analysis\ApkUnreadableException;
use App\Services\Analysis\Link\DomainParser;
use App\Services\Analysis\LinkAnalyzer;
use App\Services\Analysis\Signal;
use App\Services\Analysis\TextAnalyzer;
use App\Services\Scoring\RiskScorer;
use Illuminate\Support\Facades\DB;

/**
 * Pintu masuk pemeriksaan, dipakai bersama oleh web dan bot WhatsApp.
 *
 * Yang disimpan hanya domain, hash file, skor, dan sinyal. Isi pesan tidak disimpan.
 */
class CheckService
{
    // Teks di luar link dianggap pesan jika minimal sepanjang ini.
    private const MIN_MESSAGE_LENGTH = 12;

    public function __construct(
        private DomainParser $parser,
        private LinkAnalyzer $links,
        private ApkAnalyzer $apks,
        private TextAnalyzer $texts,
        private RiskScorer $scorer,
    ) {}

    /**
     * Cek link atau pesan teks. Link pertama di teks diperiksa; sisa teks dicek polanya.
     */
    public function checkMessage(string $text, string $source, ?string $senderHash = null): Check
    {
        $urls = $this->parser->extractUrls($text);
        $signals = [];
        $domain = null;

        if ($urls !== []) {
            $linkSignals = $this->links->analyze($urls[0]);
            $domain = $linkSignals[0]->meta['domain'] ?? null;
            array_push($signals, ...$linkSignals);
        }

        $rest = trim(str_ireplace($urls, ' ', $text));

        if ($urls === [] || mb_strlen($rest) >= self::MIN_MESSAGE_LENGTH) {
            array_push($signals, ...$this->texts->analyze($text));
        }

        return $this->store(
            $urls !== [] ? InputType::Link : InputType::Text,
            $signals,
            $source,
            $senderHash,
            domain: $domain,
        );
    }

    /**
     * Cek file APK. Pemanggil bertanggung jawab menghapus file setelahnya.
     *
     * @throws ApkUnreadableException
     */
    public function checkApk(string $path, string $fileName, string $source, ?string $senderHash = null): Check
    {
        return $this->storeApk($this->apks->analyze($path, $fileName), $source, $senderHash);
    }

    /**
     * Cek APK dari manifest yang diekstrak browser (untuk file besar).
     *
     * @throws ApkUnreadableException
     */
    public function checkApkManifest(string $manifest, string $fileName, string $sha256, int $sizeBytes, string $source): Check
    {
        return $this->storeApk($this->apks->analyzeManifest($manifest, $fileName, $sha256, $sizeBytes), $source, null);
    }

    /**
     * @param  list<Signal>  $signals
     */
    private function storeApk(array $signals, string $source, ?string $senderHash): Check
    {
        return $this->store(
            InputType::Apk,
            $signals,
            $source,
            $senderHash,
            fileSha256: $signals[0]->meta['sha256'] ?? null,
        );
    }

    /**
     * @param  list<Signal>  $signals
     */
    private function store(
        InputType $type,
        array $signals,
        string $source,
        ?string $senderHash,
        ?string $domain = null,
        ?string $fileSha256 = null,
    ): Check {
        $risk = $this->scorer->score($signals);

        return DB::transaction(function () use ($type, $signals, $source, $senderHash, $domain, $fileSha256, $risk) {
            $check = Check::create([
                'source' => $source,
                'sender_hash' => $senderHash,
                'input_type' => $type,
                'domain' => $domain,
                'file_sha256' => $fileSha256,
                'score' => $risk->score,
                'status' => $risk->status,
            ]);

            $check->signals()->createMany(array_map(fn (Signal $signal) => [
                'code' => $signal->code,
                'weight' => $signal->weight,
                'meta' => $signal->meta ?: null,
            ], $signals));

            $this->recordBrandThreats($signals, $domain);

            return $check;
        });
    }

    /**
     * Catat domain tiruan dan APK palsu untuk dashboard organisasi.
     *
     * @param  list<Signal>  $signals
     */
    private function recordBrandThreats(array $signals, ?string $domain): void
    {
        foreach ($signals as $signal) {
            $brandId = $signal->meta['brand_id'] ?? null;

            if ($brandId === null) {
                continue;
            }

            if ($signal->code === 'link.lookalike' && $domain !== null) {
                $this->recordLookalike($brandId, $domain, $signal, $signals);
            }

            if ($signal->code === 'apk.brand_impersonation') {
                $this->recordFakeApk($brandId, $signals[0]->meta);
            }
        }
    }

    /**
     * @param  list<Signal>  $signals
     */
    private function recordLookalike(int $brandId, string $domain, Signal $signal, array $signals): void
    {
        $registeredAt = $signals[0]->meta['registered_at'] ?? null;

        $lookalike = LookalikeDomain::firstOrNew(['brand_id' => $brandId, 'domain' => $domain]);

        $lookalike->fill([
            'technique' => $lookalike->technique ?? $signal->meta['technique'],
            'source' => $lookalike->source ?? 'user',
            'registered_at' => $lookalike->registered_at ?? $registeredAt,
            'first_seen_at' => $lookalike->first_seen_at ?? now(),
            'last_checked_at' => now(),
            'status' => 'active',
            'times_reported' => $lookalike->times_reported + 1,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $info  Meta dari sinyal apk.info.
     */
    private function recordFakeApk(int $brandId, array $info): void
    {
        $fake = FakeApk::firstOrNew(['brand_id' => $brandId, 'file_sha256' => $info['sha256']]);

        $fake->fill([
            'package_name' => $info['package'] ?? null,
            'app_label' => $info['label'] ?? null,
            'permissions' => $info['permissions'] ?? [],
            'first_seen_at' => $fake->first_seen_at ?? now(),
            'last_seen_at' => now(),
            'times_seen' => $fake->exists ? $fake->times_seen + 1 : 1,
        ])->save();
    }
}
