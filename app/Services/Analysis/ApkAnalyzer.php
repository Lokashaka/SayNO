<?php

namespace App\Services\Analysis;

use App\Models\Brand;
use App\Services\Analysis\Apk\AxmlException;
use App\Services\Analysis\Apk\AxmlParser;
use Illuminate\Support\Collection;

/**
 * Analisis statis APK: dibaca sebagai ZIP, hanya manifest yang diambil, file tidak pernah dijalankan.
 */
class ApkAnalyzer
{
    private const ACCESSIBILITY_PERMISSION = 'android.permission.BIND_ACCESSIBILITY_SERVICE';

    private const ACCESSIBILITY_ACTION = 'android.accessibilityservice.AccessibilityService';

    private const SMS_RECEIVED_ACTION = 'android.provider.Telephony.SMS_RECEIVED';

    // Manifest wajar jauh di bawah ini; batas mencegah "zip bomb" pada entri manifest.
    private const MAX_MANIFEST_BYTES = 4 * 1024 * 1024;

    public function __construct(private AxmlParser $parser) {}

    /**
     * Sinyal pertama selalu `apk.info` (info) yang memuat identitas aplikasi dan hash file.
     *
     * @return list<Signal>
     *
     * @throws ApkUnreadableException
     */
    public function analyze(string $path, string $fileName): array
    {
        return $this->analyzeManifest(
            $this->readManifestFromZip($path),
            $fileName,
            hash_file('sha256', $path),
            filesize($path),
            'server',
        );
    }

    /**
     * Analisis dari AndroidManifest.xml biner saja. Dipakai untuk APK besar: browser
     * mengekstrak manifest dan menghitung SHA-256, sehingga file utuh tidak perlu diunggah.
     *
     * @return list<Signal>
     *
     * @throws ApkUnreadableException
     */
    public function analyzeManifest(string $manifestData, string $fileName, string $sha256, ?int $sizeBytes = null, string $readBy = 'browser'): array
    {
        if ($manifestData === '' || strlen($manifestData) > self::MAX_MANIFEST_BYTES) {
            throw new ApkUnreadableException('AndroidManifest.xml kosong atau terlalu besar.');
        }

        try {
            $manifest = $this->summarize($this->parser->parse($manifestData));
        } catch (AxmlException $e) {
            throw new ApkUnreadableException($e->getMessage(), previous: $e);
        }

        $permissions = $manifest['permissions'];

        $info = Signal::info('apk.info', [
            'sha256' => $sha256,
            'package' => $manifest['package'],
            'label' => $manifest['label'],
            'version' => $manifest['version'],
            'permissions' => $permissions,
            'size_bytes' => $sizeBytes,
            'read_by' => $readBy,
        ]);

        $signals = [];

        if ($this->has($permissions, 'READ_SMS') || $this->has($permissions, 'RECEIVE_SMS') || $manifest['sms_receiver']) {
            $signals[] = Signal::make('apk.read_sms');
        }

        if ($manifest['accessibility']) {
            $signals[] = Signal::make('apk.accessibility');
        }

        if ($this->has($permissions, 'SYSTEM_ALERT_WINDOW')) {
            $signals[] = Signal::make('apk.overlay');
        }

        if ($this->has($permissions, 'REQUEST_INSTALL_PACKAGES')) {
            $signals[] = Signal::make('apk.install_packages');
        }

        if ($this->has($permissions, 'READ_CONTACTS') && $this->has($permissions, 'SEND_SMS')) {
            $signals[] = Signal::make('apk.spread_sms');
        }

        $keyword = $this->documentKeyword($fileName);

        if ($keyword !== null) {
            $signals[] = Signal::make('apk.document_disguise', ['keyword' => $keyword]);
        }

        $brand = $this->impersonatedBrand($manifest['package'], $manifest['label'], $fileName);

        if ($brand !== null) {
            $signals[] = Signal::make('apk.brand_impersonation', ['brand_id' => $brand->id, 'name' => $brand->name]);
        }

        return [$info, ...$signals];
    }

    /**
     * Baca entri AndroidManifest.xml langsung dari struktur ZIP (central directory), lalu coba
     * "deflate" dan "disimpan" tanpa memercayai kolom metode kompresi, karena APK berbahaya
     * sering memalsukannya. ZipArchive tidak dipakai: pada entri yang dipalsukan ia gagal
     * membaca dan membiarkan file tetap terbuka. Logika yang sama ada di resources/js/apk-manifest.js.
     */
    private function readManifestFromZip(string $path): string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new ApkUnreadableException('File tidak bisa dibuka.');
        }

        try {
            $entry = $this->findManifestEntry($handle, (int) filesize($path));

            fseek($handle, $entry['offset']);
            $local = (string) fread($handle, 30);

            if (strlen($local) < 30 || substr($local, 0, 4) !== "PK\x03\x04") {
                throw new ApkUnreadableException('Entri manifest rusak.');
            }

            $lengths = unpack('vname/vextra', substr($local, 26, 4));
            fseek($handle, $entry['offset'] + 30 + $lengths['name'] + $lengths['extra']);
            $raw = (string) fread($handle, max(1, min(max($entry['compressed'], $entry['size']), self::MAX_MANIFEST_BYTES)));
        } finally {
            fclose($handle);
        }

        foreach ([@gzinflate($raw, self::MAX_MANIFEST_BYTES), $raw] as $candidate) {
            if (is_string($candidate) && $this->isBinaryXml($candidate)) {
                return $candidate;
            }
        }

        throw new ApkUnreadableException('AndroidManifest.xml tidak bisa dibaca.');
    }

    /**
     * @param  resource  $handle
     * @return array{compressed: int, size: int, offset: int}
     */
    private function findManifestEntry($handle, int $fileSize): array
    {
        $tailLength = min($fileSize, 22 + 0xFFFF);
        fseek($handle, $fileSize - $tailLength);
        $tail = (string) fread($handle, $tailLength);
        $eocd = strrpos($tail, "PK\x05\x06");

        if ($eocd === false || $eocd + 22 > strlen($tail)) {
            throw new ApkUnreadableException('Bukan file ZIP.');
        }

        ['size' => $centralSize, 'offset' => $centralOffset] = unpack('Vsize/Voffset', substr($tail, $eocd + 12, 8));
        fseek($handle, $centralOffset);
        $central = (string) fread($handle, max(1, min($centralSize, 16 * 1024 * 1024)));

        for ($p = 0; $p + 46 <= strlen($central) && substr($central, $p, 4) === "PK\x01\x02";) {
            $entry = unpack('Vcompressed/Vsize', substr($central, $p + 20, 8))
                + unpack('vname/vextra/vcomment', substr($central, $p + 28, 6))
                + unpack('Voffset', substr($central, $p + 42, 4));

            if (substr($central, $p + 46, $entry['name']) === 'AndroidManifest.xml') {
                return $entry;
            }

            $p += 46 + $entry['name'] + $entry['extra'] + $entry['comment'];
        }

        throw new ApkUnreadableException('AndroidManifest.xml tidak ada.');
    }

    private function isBinaryXml(string $data): bool
    {
        return strlen($data) >= 8 && $data[0] === "\x03" && $data[1] === "\x00";
    }

    /**
     * @param  list<array{name: string, depth: int, attributes: array<string, string>}>  $elements
     * @return array{package: ?string, label: ?string, version: ?string, permissions: list<string>, accessibility: bool, sms_receiver: bool}
     */
    private function summarize(array $elements): array
    {
        $result = [
            'package' => null,
            'label' => null,
            'version' => null,
            'permissions' => [],
            'accessibility' => false,
            'sms_receiver' => false,
        ];

        foreach ($elements as $element) {
            $attributes = $element['attributes'];

            switch ($element['name']) {
                case 'manifest':
                    $result['package'] = $attributes['package'] ?? null;
                    $result['version'] = $attributes['versionName'] ?? null;
                    break;

                case 'application':
                    // Label berupa rujukan resource (@0x...) tidak dibaca; perlu resources.arsc.
                    $label = $attributes['label'] ?? null;
                    $result['label'] = ($label !== null && ! str_starts_with($label, '@')) ? $label : null;
                    break;

                case 'uses-permission':
                case 'uses-permission-sdk-23':
                    if (! empty($attributes['name'])) {
                        $result['permissions'][] = $attributes['name'];
                    }
                    break;

                case 'service':
                    if (($attributes['permission'] ?? null) === self::ACCESSIBILITY_PERMISSION) {
                        $result['accessibility'] = true;
                    }
                    break;

                case 'action':
                    $action = $attributes['name'] ?? null;
                    $result['accessibility'] = $result['accessibility'] || $action === self::ACCESSIBILITY_ACTION;
                    $result['sms_receiver'] = $result['sms_receiver'] || $action === self::SMS_RECEIVED_ACTION;
                    break;
            }
        }

        $result['permissions'] = array_values(array_unique($result['permissions']));

        return $result;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function has(array $permissions, string $name): bool
    {
        return in_array('android.permission.'.$name, $permissions, true);
    }

    private function documentKeyword(string $fileName): ?string
    {
        $tokens = $this->tokens(pathinfo($fileName, PATHINFO_FILENAME));

        foreach (config('sayno.apk.document_keywords') as $keyword) {
            foreach ($tokens as $token) {
                if ($token === $keyword || (strlen($keyword) >= 5 && str_contains($token, $keyword))) {
                    return $keyword;
                }
            }
        }

        return null;
    }

    private function impersonatedBrand(?string $package, ?string $label, string $fileName): ?Brand
    {
        $tokens = [
            ...$this->tokens((string) $package),
            ...$this->tokens((string) $label),
            ...$this->tokens(pathinfo($fileName, PATHINFO_FILENAME)),
        ];

        /** @var Collection<int, Brand> $brands */
        $brands = Brand::query()->orderBy('id')->get();

        foreach ($brands as $brand) {
            if ($brand->isOfficialPackage($package)) {
                continue;
            }

            if (array_intersect($brand->matchKeywords(), $tokens) !== []) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Pecah nama menjadi kata huruf kecil: "BCA_Mobile-v2" -> [bca, mobile, v2].
     *
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        $value = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $value);

        return array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($value)) ?: []));
    }
}
