<?php

namespace Tests\Unit\Analysis;

use App\Models\ScamPattern;
use App\Services\Analysis\Signal;
use App\Services\Analysis\TextAnalyzer;
use Database\Seeders\ScamPatternSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ScamPatternSeeder::class);
    }

    #[Test]
    #[DataProvider('scamMessages')]
    public function it_recognizes_scam_messages(string $category, string $message): void
    {
        $signals = app(TextAnalyzer::class)->analyze($message);

        $this->assertContains($category, array_map(fn (Signal $s) => $s->meta['category'], $signals));
        $this->assertSame(20, $signals[0]->weight);
    }

    #[Test]
    #[DataProvider('benignMessages')]
    public function it_leaves_everyday_messages_alone(string $message): void
    {
        $this->assertSame([], app(TextAnalyzer::class)->analyze($message));
    }

    #[Test]
    public function it_counts_at_most_two_patterns(): void
    {
        $message = 'Selamat Anda terpilih mendapatkan hadiah undian! Akun Anda akan diblokir, kirim kode OTP. Paket Anda tertahan di bea cukai.';

        $this->assertCount(2, app(TextAnalyzer::class)->analyze($message));
    }

    #[Test]
    public function it_skips_broken_patterns(): void
    {
        ScamPattern::create(['category' => 'hadiah', 'pattern' => '(rusak', 'is_regex' => true]);

        $this->assertSame([], app(TextAnalyzer::class)->analyze('Halo, apa kabar?'));
    }

    public static function scamMessages(): array
    {
        return self::read('scam.txt', withCategory: true);
    }

    public static function benignMessages(): array
    {
        return self::read('benign.txt', withCategory: false);
    }

    private static function read(string $file, bool $withCategory): array
    {
        $content = file_get_contents(__DIR__.'/../../Fixtures/text/'.$file);
        $cases = [];

        foreach (preg_split('/\R\s*\R/', $content) as $block) {
            $lines = array_values(array_filter(preg_split('/\R/', trim($block)), fn ($line) => ! str_starts_with($line, '#')));

            if ($lines === []) {
                continue;
            }

            if ($withCategory) {
                $category = trim(substr(array_shift($lines), 2));
                $cases[] = [$category, implode("\n", $lines)];
            } else {
                $cases[] = [implode("\n", $lines)];
            }
        }

        return $cases;
    }
}
