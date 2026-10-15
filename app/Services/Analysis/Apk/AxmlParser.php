<?php

namespace App\Services\Analysis\Apk;

/**
 * Pembaca XML biner Android (AXML) untuk AndroidManifest.xml, dalam PHP murni.
 *
 * Sengaja longgar terhadap header yang dimanipulasi (trik umum APK berbahaya untuk
 * merusak alat analisis), tetapi setiap pembacaan dicek batasnya.
 */
class AxmlParser
{
    private const CHUNK_STRING_POOL = 0x0001;

    private const CHUNK_RESOURCE_MAP = 0x0180;

    private const CHUNK_START_ELEMENT = 0x0102;

    private const CHUNK_END_ELEMENT = 0x0103;

    private const UTF8_FLAG = 0x100;

    private const NO_ENTRY = 0xFFFFFFFF;

    // ID atribut android:* yang dipakai bila nama atribut dikosongkan oleh obfuscator.
    private const ATTRIBUTE_IDS = [
        0x01010001 => 'label',
        0x01010003 => 'name',
        0x01010006 => 'permission',
        0x0101021B => 'versionCode',
        0x0101021C => 'versionName',
    ];

    private string $data;

    private int $length;

    /** @var list<string> */
    private array $strings = [];

    /** @var list<int> */
    private array $resourceIds = [];

    /**
     * Daftar elemen dalam urutan dokumen.
     *
     * @return list<array{name: string, depth: int, attributes: array<string, string>}>
     */
    public function parse(string $data): array
    {
        $this->data = $data;
        $this->length = strlen($data);
        $this->strings = [];
        $this->resourceIds = [];

        if ($this->length < 8) {
            throw new AxmlException('Manifest terlalu pendek.');
        }

        $elements = [];
        $depth = 0;
        $offset = $this->u16(2); // header size dokumen, biasanya 8

        if ($offset < 8 || $offset > $this->length) {
            $offset = 8;
        }

        while ($offset + 8 <= $this->length) {
            $type = $this->u16($offset);
            $headerSize = $this->u16($offset + 2);
            $size = $this->u32($offset + 4);

            if ($size < 8 || $offset + $size > $this->length) {
                throw new AxmlException('Ukuran chunk tidak valid.');
            }

            match ($type) {
                self::CHUNK_STRING_POOL => $this->readStringPool($offset),
                self::CHUNK_RESOURCE_MAP => $this->readResourceMap($offset, $headerSize, $size),
                default => null,
            };

            if ($type === self::CHUNK_START_ELEMENT) {
                $elements[] = $this->readElement($offset, $headerSize, $depth);
                $depth++;
            } elseif ($type === self::CHUNK_END_ELEMENT) {
                $depth = max(0, $depth - 1);
            }

            $offset += $size;
        }

        if ($elements === []) {
            throw new AxmlException('Manifest tidak berisi elemen.');
        }

        return $elements;
    }

    private function readStringPool(int $offset): void
    {
        $count = $this->u32($offset + 8);
        $flags = $this->u32($offset + 16);
        $stringsStart = $this->u32($offset + 20);
        $headerSize = $this->u16($offset + 2);
        $utf8 = ($flags & self::UTF8_FLAG) !== 0;

        if ($count > 200000) {
            throw new AxmlException('Jumlah string tidak wajar.');
        }

        for ($i = 0; $i < $count; $i++) {
            $stringOffset = $offset + $stringsStart + $this->u32($offset + $headerSize + $i * 4);
            $this->strings[] = $utf8 ? $this->utf8String($stringOffset) : $this->utf16String($stringOffset);
        }
    }

    private function readResourceMap(int $offset, int $headerSize, int $size): void
    {
        $count = intdiv($size - $headerSize, 4);

        for ($i = 0; $i < $count; $i++) {
            $this->resourceIds[] = $this->u32($offset + $headerSize + $i * 4);
        }
    }

    /**
     * @return array{name: string, depth: int, attributes: array<string, string>}
     */
    private function readElement(int $offset, int $headerSize, int $depth): array
    {
        $body = $offset + $headerSize;
        $name = $this->string($this->u32($body + 4));
        $attributeStart = $this->u16($body + 8);
        $attributeSize = $this->u16($body + 10) ?: 20;
        $attributeCount = $this->u16($body + 12);

        $attributes = [];

        for ($i = 0; $i < $attributeCount; $i++) {
            $attr = $body + $attributeStart + $i * $attributeSize;
            $nameIndex = $this->u32($attr + 4);
            $attributeName = $this->string($nameIndex);

            if ($attributeName === '' || ! preg_match('/^[A-Za-z_][\w.-]*$/', $attributeName)) {
                $attributeName = self::ATTRIBUTE_IDS[$this->resourceIds[$nameIndex] ?? -1] ?? $attributeName;
            }

            if ($attributeName === '') {
                continue;
            }

            $attributes[$attributeName] = $this->attributeValue($attr);
        }

        return ['name' => $name, 'depth' => $depth, 'attributes' => $attributes];
    }

    private function attributeValue(int $attr): string
    {
        $raw = $this->u32($attr + 8);
        $dataType = ord($this->bytes($attr + 15, 1));
        $data = $this->u32($attr + 16);

        if ($raw !== self::NO_ENTRY) {
            return $this->string($raw);
        }

        return match ($dataType) {
            0x03 => $this->string($data),
            0x01 => sprintf('@0x%08x', $data),
            0x10 => (string) ($data >= 0x80000000 ? $data - 0x100000000 : $data),
            0x11 => sprintf('0x%x', $data),
            0x12 => $data !== 0 ? 'true' : 'false',
            default => (string) $data,
        };
    }

    private function string(int $index): string
    {
        return $index === self::NO_ENTRY ? '' : ($this->strings[$index] ?? '');
    }

    private function utf16String(int $offset): string
    {
        $length = $this->u16($offset);
        $offset += 2;

        if ($length & 0x8000) {
            $length = (($length & 0x7FFF) << 16) | $this->u16($offset);
            $offset += 2;
        }

        return mb_convert_encoding($this->bytes($offset, $length * 2), 'UTF-8', 'UTF-16LE');
    }

    private function utf8String(int $offset): string
    {
        // Panjang UTF-16 (dilewati), lalu panjang byte UTF-8.
        $offset += (ord($this->bytes($offset, 1)) & 0x80) ? 2 : 1;
        $length = ord($this->bytes($offset, 1));

        if ($length & 0x80) {
            $length = (($length & 0x7F) << 8) | ord($this->bytes($offset + 1, 1));
            $offset += 2;
        } else {
            $offset += 1;
        }

        return mb_scrub($this->bytes($offset, $length), 'UTF-8');
    }

    private function u16(int $offset): int
    {
        return unpack('v', $this->bytes($offset, 2))[1];
    }

    private function u32(int $offset): int
    {
        return unpack('V', $this->bytes($offset, 4))[1];
    }

    private function bytes(int $offset, int $length): string
    {
        if ($offset < 0 || $length < 0 || $offset + $length > $this->length) {
            throw new AxmlException('Pembacaan di luar batas manifest.');
        }

        return substr($this->data, $offset, $length);
    }
}
