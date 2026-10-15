<?php

namespace App\Services\Reply;

use App\Enums\Status;
use App\Models\Check;
use App\Models\Signal as SignalRecord;
use App\Services\Analysis\Signal;
use App\Services\Scoring\RiskScorer;

/**
 * Menyusun hasil pemeriksaan dalam bahasa awam, untuk web dan balasan WhatsApp.
 * Semua kalimat diambil dari lang/id.
 */
class ReplyComposer
{
    public function __construct(private RiskScorer $scorer) {}

    /**
     * @return array{status: Status, type: string, title: string, reasons: list<string>, actions: list<string>}
     */
    public function summary(Check $check): array
    {
        $signals = $check->signals->map(fn (SignalRecord $record) => new Signal(
            $record->code,
            $record->weight,
            "signals.{$record->code}",
            $record->meta ?? [],
        ))->all();

        $risk = $this->scorer->score($signals);
        $type = $check->input_type->value;
        $status = $check->status;

        $reasons = array_map(fn (Signal $signal) => $this->reason($signal), $risk->reasons);

        if ($risk->isOfficial) {
            $official = collect($signals)->firstWhere('code', 'link.official');
            array_unshift($reasons, $this->reason($official));
        }

        if ($reasons === []) {
            $reasons[] = __("result.no_findings.{$type}");
        }

        return [
            'status' => $status,
            'type' => $type,
            'title' => __("result.title.{$status->value}.{$type}"),
            'reasons' => array_slice($reasons, 0, config('sayno.max_reasons')),
            'actions' => $this->actions($status, $type, $signals),
        ];
    }

    /**
     * Balasan WhatsApp, sekitar 6 baris, memakai format tebal *teks*.
     */
    public function whatsApp(Check $check, ?string $fileName = null): string
    {
        $summary = $this->summary($check);
        $status = $summary['status'];

        $lines = [$status->emoji().' *'.__("result.headline.{$status->value}.{$summary['type']}").'*'];

        if ($fileName !== null && $summary['type'] === 'apk') {
            $lines[] = __('result.file_name', ['name' => $fileName]);
        }

        array_push($lines, ...array_slice($summary['reasons'], 0, 2));
        $lines[] = '';
        $lines[] = __('result.what_to_do');

        foreach (array_slice($summary['actions'], 0, 2) as $i => $action) {
            $lines[] = ($i + 1).'. '.$action;
        }

        return implode("\n", $lines);
    }

    private function reason(Signal $signal): string
    {
        $meta = $signal->meta;

        if ($signal->code === 'link.new_domain') {
            $meta['age'] = $this->age((int) ($meta['age_days'] ?? 0));
        }

        if ($signal->code === 'text.pattern') {
            $meta['category'] = __('modus.categories.'.($meta['category'] ?? 'lainnya'));
        }

        if ($signal->code === 'link.lookalike') {
            $meta['technique'] = __('signals.techniques.'.($meta['technique'] ?? 'combo'));
        }

        return __($signal->reason, array_filter($meta, 'is_scalar'));
    }

    /**
     * @param  list<Signal>  $signals
     * @return list<string>
     */
    private function actions(Status $status, string $type, array $signals): array
    {
        $actions = __("result.actions.{$status->value}.{$type}");

        $lookalike = collect($signals)->firstWhere('code', 'link.lookalike');

        if ($lookalike !== null && $status !== Status::Safe) {
            $actions[] = __('result.official_site', ['domain' => $lookalike->meta['official']]);
        }

        return $actions;
    }

    private function age(int $days): string
    {
        return $days === 0 ? __('result.age.today') : __('result.age.days', ['days' => $days]);
    }
}
