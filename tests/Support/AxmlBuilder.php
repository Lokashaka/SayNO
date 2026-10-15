<?php

namespace Tests\Support;

use ZipArchive;

/**
 * Membuat AndroidManifest.xml biner (AXML, string pool UTF-8) untuk test,
 * sehingga tidak perlu menyimpan APK berbahaya di repo.
 */
class AxmlBuilder
{
    private const NO_ENTRY = 0xFFFFFFFF;

    /** @var list<string> */
    private array $strings = [];

    /**
     * @param  array{0: string, 1: array<string, string>, 2?: list<array>}  $root  [nama, atribut, anak]
     */
    public function build(array $root): string
    {
        $this->strings = [];
        $this->collect($root);

        $body = $this->stringPool().$this->element($root);

        return pack('vvV', 0x0003, 8, 8 + strlen($body)).$body;
    }

    /**
     * Tulis APK (ZIP) berisi manifest ke path sementara dan kembalikan path-nya.
     *
     * @param  array{0: string, 1: array<string, string>, 2?: list<array>}  $root
     */
    public static function apk(array $root, ?string $manifestOverride = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sayno-test-').'.apk';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('AndroidManifest.xml', $manifestOverride ?? (new self)->build($root));
        $zip->addFromString('classes.dex', 'dex');
        $zip->close();

        return $path;
    }

    private function collect(array $node): void
    {
        $this->index($node[0]);

        foreach ($node[1] as $name => $value) {
            $this->index($name);
            $this->index($value);
        }

        foreach ($node[2] ?? [] as $child) {
            $this->collect($child);
        }
    }

    private function index(string $value): int
    {
        $position = array_search($value, $this->strings, true);

        if ($position === false) {
            $this->strings[] = $value;

            return count($this->strings) - 1;
        }

        return $position;
    }

    private function stringPool(): string
    {
        $offsets = '';
        $data = '';

        foreach ($this->strings as $string) {
            $offsets .= pack('V', strlen($data));
            $length = strlen($string);
            $data .= chr(mb_strlen($string)).chr($length).$string."\0";
        }

        $data .= str_repeat("\0", (4 - strlen($data) % 4) % 4);
        $headerSize = 28;
        $stringsStart = $headerSize + strlen($offsets);
        $size = $stringsStart + strlen($data);

        return pack('vvVVVVVV', 0x0001, $headerSize, $size, count($this->strings), 0, 0x100, $stringsStart, 0)
            .$offsets.$data;
    }

    private function element(array $node): string
    {
        [$name, $attributes] = $node;
        $nameIndex = $this->index($name);

        $attributeData = '';

        foreach ($attributes as $attribute => $value) {
            $valueIndex = $this->index($value);
            $attributeData .= pack('VVVvCCV', self::NO_ENTRY, $this->index($attribute), $valueIndex, 8, 0, 0x03, $valueIndex);
        }

        $start = pack('vvVVV', 0x0102, 16, 16 + 20 + strlen($attributeData), 1, self::NO_ENTRY)
            .pack('VVvvvvvv', self::NO_ENTRY, $nameIndex, 20, 20, count($attributes), 0, 0, 0)
            .$attributeData;

        $children = '';

        foreach ($node[2] ?? [] as $child) {
            $children .= $this->element($child);
        }

        $end = pack('vvVVVVV', 0x0103, 16, 24, 1, self::NO_ENTRY, self::NO_ENTRY, $nameIndex);

        return $start.$children.$end;
    }
}
