<?php

namespace App\Services\Charts;

use App\Enums\ChartDifficultyName;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Services\Maidata\MaidataLineEndings;
use App\Services\Maidata\MaidataParser;
use App\Services\Maidata\MaidataSerializer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Updates a single difficulty's simai chart code, keeping the stored maidata
 * file and the downloadable package in sync.
 */
class ChartDifficultyService
{
    /**
     * The chart body a new difficulty starts with until the owner replaces it
     * on the difficulty's Code tab.
     */
    public const PLACEHOLDER_CODE = '|| insert code here';

    /**
     * Canonical maidata chart index for each addable difficulty type.
     *
     * @var array<string, string>
     */
    private const CANONICAL_INDEXES = [
        'easy' => '1',
        'basic' => '2',
        'advanced' => '3',
        'expert' => '4',
        'master' => '5',
        'remaster' => '6',
        'utage' => '7',
    ];

    public function __construct(
        private readonly MaidataSerializer $serializer,
        private readonly MaidataParser $parser,
        private readonly ChartAssetService $assets,
        private readonly ChartDifficultyRevisionService $revisions,
    ) {}

    /**
     * Add a difficulty to a chart, keeping the stored maidata file in sync.
     *
     * The new difficulty starts with the placeholder level value "-", a
     * charter falling back to the chart's note designer, and
     * {@see PLACEHOLDER_CODE} as its chart body, so the maidata parser
     * recognises it immediately and the settings tab keeps working. An
     * original revision records the placeholder code.
     *
     * @throws ChartUploadException when the rewritten maidata fails to parse.
     * @throws ValidationException when no chart index is available.
     */
    public function create(Chart $chart, ChartDifficultyName $difficulty): ChartDifficulty
    {
        $index = $this->resolveChartIndex($chart, $difficulty);
        $charter = $chart->note_designer ?: '-';

        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;
        $updated = null;

        if ($maidataPath !== null && $disk->exists($maidataPath)) {
            $values = [
                "lv_{$index}" => '-',
                "des_{$index}" => $charter,
            ];

            if ((self::CANONICAL_INDEXES[$difficulty->value] ?? null) !== $index) {
                $values["alias_{$index}"] = $difficulty->value;
            }

            $updated = $this->serializer->replaceChartCode(
                $this->serializer->replaceHeaderValues($disk->get($maidataPath), $values),
                $index,
                self::PLACEHOLDER_CODE,
            );

            $parsed = $this->parser->parse($updated);

            if ($parsed->logs->hasErrors()) {
                throw new ChartUploadException($parsed->logs->errors);
            }
        }

        $created = $chart->difficulties()->create([
            'chart_index' => $index,
            'difficulty' => $difficulty,
            'difficulty_value' => '-',
            'charter' => $charter,
            'code' => self::PLACEHOLDER_CODE,
        ]);

        $this->revisions->recordOriginal($created, self::PLACEHOLDER_CODE, $chart->uploader_id);

        if ($updated !== null) {
            $disk->put($maidataPath, $updated);

            $this->rebuildDownload($disk, $chart);
        }

        return $created;
    }

    /**
     * Replace the given difficulty's code.
     *
     * A revision is snapshotted when the code actually changes (see
     * {@see ChartDifficultyRevisionService::record()}).
     */
    public function updateCode(Chart $chart, ChartDifficulty $difficulty, string $code, ?int $userId = null): void
    {
        // Normalise before comparing so a CRLF payload that is otherwise
        // identical to the stored code does not record a spurious revision.
        $code = trim(MaidataLineEndings::normalize($code));
        $before = $difficulty->code;

        $difficulty->code = $code;
        $difficulty->save();

        $this->revisions->record($difficulty, $before, $code, $userId);

        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath === null || ! $disk->exists($maidataPath)) {
            return;
        }

        $disk->put($maidataPath, $this->serializer->replaceChartCode(
            $disk->get($maidataPath),
            $difficulty->chart_index,
            $code,
        ));

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Update the editable metadata (level value and charter) of a difficulty.
     *
     * When the chart has a stored maidata file the matching `&lv_#=` and
     * `&des_#=` headers are rewritten in place and the downloadable package is
     * rebuilt. A missing value removes the corresponding header. The difficulty
     * columns are synced from the freshly parsed maidata so the database and the
     * file never drift apart.
     *
     * @param  array{difficulty_value?: string|null, charter?: string|null}  $metadata
     *
     * @throws ChartUploadException when the rewritten maidata fails to parse.
     */
    public function updateMetadata(Chart $chart, ChartDifficulty $difficulty, array $metadata): void
    {
        $values = [
            'difficulty_value' => $metadata['difficulty_value'] ?? null,
            'charter' => $metadata['charter'] ?? null,
        ];

        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath === null || ! $disk->exists($maidataPath)) {
            $difficulty->fill($values)->save();

            return;
        }

        $updated = $this->serializer->replaceHeaderValues($disk->get($maidataPath), [
            "lv_{$difficulty->chart_index}" => $values['difficulty_value'],
            "des_{$difficulty->chart_index}" => $values['charter'],
        ]);

        $parsed = $this->parser->parse($updated);

        if ($parsed->logs->hasErrors()) {
            throw new ChartUploadException($parsed->logs->errors);
        }

        $parsedChart = collect($parsed->charts)->firstWhere('chartIndex', $difficulty->chart_index);

        $difficulty->fill([
            'difficulty_value' => $parsedChart?->difficultyValue,
            'charter' => $parsedChart?->charter,
        ])->save();

        $disk->put($maidataPath, $updated);

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Delete a difficulty, removing its headers from the maidata file and
     * rebuilding the downloadable package.
     *
     * The difficulty's revisions, improvements, and comments are removed by the
     * database's cascading foreign keys.
     */
    public function delete(Chart $chart, ChartDifficulty $difficulty): void
    {
        $disk = Storage::disk('local');
        $maidataPath = $chart->maidata_path;

        if ($maidataPath !== null && $disk->exists($maidataPath)) {
            $disk->put($maidataPath, $this->serializer->removeChart(
                $disk->get($maidataPath),
                $difficulty->chart_index,
            ));
        }

        $difficulty->delete();

        $this->rebuildDownload($disk, $chart);
    }

    /**
     * Pick the maidata chart index for a new difficulty: its canonical index
     * when that slot is free, otherwise the first free index from 1 to 9.
     *
     * @throws ValidationException when every chart index is already taken.
     */
    private function resolveChartIndex(Chart $chart, ChartDifficultyName $difficulty): string
    {
        $taken = $chart->difficulties()->pluck('chart_index')->all();
        $canonical = self::CANONICAL_INDEXES[$difficulty->value] ?? null;

        if ($canonical !== null && ! in_array($canonical, $taken, true)) {
            return $canonical;
        }

        foreach (range('1', '9') as $candidate) {
            if (! in_array($candidate, $taken, true)) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            'difficulty' => 'This chart already uses all nine available difficulty slots.',
        ]);
    }

    /**
     * Rebuild the chart's downloadable package from its current files.
     */
    private function rebuildDownload(Filesystem $disk, Chart $chart): void
    {
        if ($chart->download_path === null) {
            return;
        }

        $this->assets->buildDownload($disk, $chart, [
            'maidata_path' => $chart->maidata_path,
            'art_path' => $chart->art_path,
            'track_path' => $chart->track_path,
            'pv_path' => $chart->pv_path,
        ], $chart->download_path);
    }
}
