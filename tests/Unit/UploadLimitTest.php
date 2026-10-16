<?php

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    #[Test]
    public function it_reads_php_size_notation(): void
    {
        $this->assertSame(10 * 1024 * 1024, UploadLimit::iniBytes('10M'));
        $this->assertSame(2 * 1024 ** 3, UploadLimit::iniBytes('2G'));
        $this->assertSame(512 * 1024, UploadLimit::iniBytes('512k'));
        $this->assertSame(0, UploadLimit::iniBytes(false));
    }

    #[Test]
    public function the_server_limit_never_exceeds_the_configured_maximum(): void
    {
        config(['sayno.apk.max_bytes' => 1024 * 1024]);

        $this->assertLessThanOrEqual(1024 * 1024, UploadLimit::apkServerBytes());
    }
}
