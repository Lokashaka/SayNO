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
        if (! preg_match('/^[a-z0-9.-]+$/', $domain)) {
            return false;
        }

        return @checkdnsrr($domain.'.', 'NS') || @checkdnsrr($domain.'.', 'A');
    }
}
