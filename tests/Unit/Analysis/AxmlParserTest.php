<?php

namespace Tests\Unit\Analysis;

use App\Services\Analysis\Apk\AxmlException;
use App\Services\Analysis\Apk\AxmlParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\AxmlBuilder;

class AxmlParserTest extends TestCase
{
    #[Test]
    public function it_reads_a_real_utf16_manifest(): void
    {
        // Manifest dari APK contoh resmi Android Studio (ComplicationWatchFace.apk).
        $elements = (new AxmlParser)->parse(file_get_contents(__DIR__.'/../../Fixtures/apk/watchface.AndroidManifest.bin'));

        $manifest = $elements[0];
        $this->assertSame('manifest', $manifest['name']);
        $this->assertSame('androidx.wear.watchface.samples.app', $manifest['attributes']['package']);
        $this->assertSame('1.0', $manifest['attributes']['versionName']);

        $permissions = array_column(array_column(array_filter($elements, fn ($e) => $e['name'] === 'uses-permission'), 'attributes'), 'name');
        $this->assertContains('android.permission.WAKE_LOCK', $permissions);
        $this->assertContains('android.permission.RECEIVE_BOOT_COMPLETED', $permissions);

        $service = collect($elements)->firstWhere('name', 'service');
        $this->assertSame('android.permission.BIND_WALLPAPER', $service['attributes']['permission']);
        $this->assertSame(2, $service['depth']);
    }

    #[Test]
    public function it_reads_a_utf8_manifest(): void
    {
        $data = (new AxmlBuilder)->build(['manifest', ['package' => 'com.contoh.aplikasi'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
        ]]);

        $elements = (new AxmlParser)->parse($data);

        $this->assertSame('com.contoh.aplikasi', $elements[0]['attributes']['package']);
        $this->assertSame('android.permission.READ_SMS', $elements[1]['attributes']['name']);
        $this->assertSame(1, $elements[1]['depth']);
    }

    #[Test]
    public function it_rejects_truncated_data_instead_of_reading_out_of_bounds(): void
    {
        $data = file_get_contents(__DIR__.'/../../Fixtures/apk/watchface.AndroidManifest.bin');

        $this->expectException(AxmlException::class);

        (new AxmlParser)->parse(substr($data, 0, 900));
    }

    #[Test]
    public function it_rejects_garbage(): void
    {
        $this->expectException(AxmlException::class);

        (new AxmlParser)->parse('<?xml version="1.0"?><manifest/>');
    }
}
