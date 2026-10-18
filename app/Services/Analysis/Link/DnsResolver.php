<?php

namespace App\Services\Analysis\Link;

/**
 * Mengecek apakah domain terdaftar lewat DNS. Hanya dipakai untuk variasi buatan
 * LookalikeGenerator, tidak untuk link kiriman pengguna.
 */
class DnsResolver
{
    public function isRegistered(string $domain): bool
    {
        if (! $this->isValid($domain)) {
            return false;
        }

        return @checkdnsrr($domain.'.', 'NS') || @checkdnsrr($domain.'.', 'A');
    }

    /**
     * @return list<string> Nama server DNS domain, huruf kecil tanpa titik di akhir.
     */
    public function nameservers(string $domain): array
    {
        if (! $this->isValid($domain)) {
            return [];
        }

        $records = @dns_get_record($domain.'.', DNS_NS) ?: [];

        return array_values(array_unique(array_map(
            fn (array $record) => rtrim(strtolower($record['target'] ?? ''), '.'),
            array_filter($records, fn (array $record) => isset($record['target'])),
        )));
    }

    private function isValid(string $domain): bool
    {
        return (bool) preg_match('/^[a-z0-9.-]+$/', $domain);
    }
}
