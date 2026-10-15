<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'primary_domain', 'official_packages', 'keywords', 'access_token_hash'])]
#[Hidden(['access_token_hash'])]
class Brand extends Model
{
    protected function casts(): array
    {
        return [
            'official_packages' => 'array',
            'keywords' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function officialDomains(): HasMany
    {
        return $this->hasMany(OfficialDomain::class);
    }

    public function lookalikeDomains(): HasMany
    {
        return $this->hasMany(LookalikeDomain::class);
    }

    public function fakeApks(): HasMany
    {
        return $this->hasMany(FakeApk::class);
    }

    /**
     * Kata yang menandakan merek ini disebut, dalam huruf kecil.
     *
     * @return list<string>
     */
    public function matchKeywords(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($keyword) => mb_strtolower(trim((string) $keyword)),
            $this->keywords ?: [$this->slug],
        ))));
    }

    public function isOfficialPackage(?string $package): bool
    {
        return $package !== null && in_array($package, $this->official_packages ?? [], true);
    }

    public function tokenMatches(?string $token): bool
    {
        return $token !== null
            && $this->access_token_hash !== null
            && hash_equals($this->access_token_hash, hash('sha256', $token));
    }
}
