<?php

namespace Tests\Unit\Scoring;

use App\Enums\Status;
use App\Services\Analysis\Signal;
use App\Services\Scoring\RiskScorer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RiskScorerTest extends TestCase
{
    #[Test]
    public function it_maps_scores_to_statuses(): void
    {
        $this->assertSame(Status::Safe, Status::fromScore(0));
        $this->assertSame(Status::Safe, Status::fromScore(29));
        $this->assertSame(Status::Caution, Status::fromScore(30));
        $this->assertSame(Status::Caution, Status::fromScore(59));
        $this->assertSame(Status::Danger, Status::fromScore(60));
        $this->assertSame(Status::Danger, Status::fromScore(100));
    }

    #[Test]
    public function it_sums_weights_from_config_and_caps_at_100(): void
    {
        $risk = (new RiskScorer)->score([
            Signal::info('apk.info'),
            Signal::make('apk.read_sms'),
            Signal::make('apk.accessibility'),
            Signal::make('apk.document_disguise'),
        ]);

        $this->assertSame(100, $risk->score);
        $this->assertSame(Status::Danger, $risk->status);
        $this->assertSame(['apk.read_sms', 'apk.accessibility', 'apk.document_disguise'], array_map(fn ($s) => $s->code, $risk->reasons));
    }

    #[Test]
    public function it_uses_weights_from_config(): void
    {
        config(['sayno.weights' => ['link.shortener' => 35] + config('sayno.weights')]);

        $risk = (new RiskScorer)->score([Signal::make('link.shortener')]);

        $this->assertSame(35, $risk->score);
        $this->assertSame(Status::Caution, $risk->status);
    }

    #[Test]
    public function an_official_domain_cancels_link_signals_but_not_scam_text(): void
    {
        $official = (new RiskScorer)->score([Signal::info('link.official'), Signal::make('link.shortener')]);
        $this->assertSame(0, $official->score);
        $this->assertTrue($official->isOfficial);

        $withText = (new RiskScorer)->score([Signal::info('link.official'), Signal::make('text.pattern'), Signal::make('text.pattern')]);
        $this->assertSame(40, $withText->score);
    }

    #[Test]
    public function it_limits_reasons_to_three(): void
    {
        $risk = (new RiskScorer)->score([
            Signal::make('link.shortener'),
            Signal::make('link.new_domain'),
            Signal::make('link.lookalike'),
            Signal::make('link.blacklisted'),
        ]);

        $this->assertSame(['link.blacklisted', 'link.lookalike', 'link.new_domain'], array_map(fn ($s) => $s->code, $risk->reasons));
    }
}
