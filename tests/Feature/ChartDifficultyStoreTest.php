<?php

use App\Enums\ChartDifficultyName;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\User;
use App\Services\Charts\ChartDifficultyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Upload a chart with a stored maidata file (Expert + Master) and return it.
 */
function difficultyAddChart(User $owner): Chart
{
    test()->actingAs($owner)->post(route('charts.store'), [
        'package' => chartPackageZip([
            'maidata.txt' => sampleMaidata(),
            'bg.jpg' => fakeJpegBytes(),
            'track.mp3' => 'fake-audio-bytes',
        ]),
    ])->assertRedirect();

    return Chart::firstOrFail();
}

test('the owner can add a missing difficulty with placeholder level, charter, and code', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('charts.show', $chart))
        ->assertSessionHas('status');

    $difficulty = $chart->difficulties()->where('difficulty', 'easy')->firstOrFail();

    expect($difficulty->chart_index)->toBe('1')
        ->and($difficulty->difficulty_value)->toBe('-')
        ->and($difficulty->charter)->toBe('Test Charter')
        ->and($difficulty->code)->toBe(ChartDifficultyService::PLACEHOLDER_CODE);

    $original = $difficulty->revisions()->where('is_original', true)->first();

    expect($original)->not->toBeNull()
        ->and($original->code)->toBe(ChartDifficultyService::PLACEHOLDER_CODE)
        ->and($original->user_id)->toBe($owner->id);

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)
        ->toContain('&inote_1='.ChartDifficultyService::PLACEHOLDER_CODE)
        ->toContain('&lv_1=-')
        ->toContain('&des_1=Test Charter')
        // Existing difficulties are untouched.
        ->toContain('&lv_4=12')
        ->toContain('&inote_5=(180){4}1,2,3,4,5,');
});

test('the charter falls back to a dash when the chart has no note designer', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);
    $chart->update(['note_designer' => null]);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('charts.show', $chart));

    $difficulty = $chart->difficulties()->where('chart_index', '1')->firstOrFail();

    expect($difficulty->charter)->toBe('-');

    expect(Storage::disk('local')->get($chart->maidata_path))
        ->toContain('&des_1=-');
});

test('a difficulty whose canonical slot is taken uses the next free index with an alias', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);
    ChartDifficulty::factory()->for($chart)->create([
        'chart_index' => '1',
        'difficulty' => ChartDifficultyName::Custom,
    ]);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('charts.show', $chart));

    $difficulty = $chart->difficulties()->where('chart_index', '2')->firstOrFail();

    expect($difficulty->difficulty)->toBe(ChartDifficultyName::Easy);

    $maidata = Storage::disk('local')->get($chart->maidata_path);

    expect($maidata)
        ->toContain('&alias_2=easy')
        ->toContain('&lv_2=-')
        ->toContain('&inote_2='.ChartDifficultyService::PLACEHOLDER_CODE);
});

test('adding is refused when every chart index is already taken', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    foreach (range('1', '9') as $slot) {
        ChartDifficulty::factory()->for($chart)->create([
            'chart_index' => (string) $slot,
            'difficulty' => ChartDifficultyName::Custom,
        ]);
    }

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertSessionHasErrors('difficulty');

    expect($chart->difficulties()->count())->toBe(9);
});

test('an already-added difficulty type is rejected', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'master'])
        ->assertSessionHasErrors('difficulty');

    expect($chart->difficulties()->count())->toBe(2);
});

test('the custom difficulty cannot be added', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'custom'])
        ->assertSessionHasErrors('difficulty');

    expect($chart->difficulties()->count())->toBe(2);
});

test('a collaborator cannot add a difficulty', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator);

    $this->actingAs($collaborator)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertNotFound();

    expect($chart->difficulties()->count())->toBe(2);
});

test('a non-owner cannot add a difficulty', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs(User::factory()->create())
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertNotFound();

    expect($chart->difficulties()->count())->toBe(2);
});

test('a guest is redirected to the login page', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    // The upload helper authenticates the owner; log out for the guest request.
    auth()->logout();

    $this->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('login'));

    expect($chart->difficulties()->count())->toBe(2);
});

test('the added difficulty can be edited in its settings tab', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('charts.show', $chart));

    $difficulty = $chart->difficulties()->where('chart_index', '1')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.settings.update', [$chart, $difficulty]), [
            'difficulty_value' => '13',
            'charter' => 'New Charter',
        ])
        ->assertRedirect(route('charts.difficulties.show', [$chart, $difficulty, 'settings']))
        ->assertSessionHas('status');

    $difficulty->refresh();

    expect($difficulty->difficulty_value)->toBe('13')
        ->and($difficulty->charter)->toBe('New Charter');

    expect(Storage::disk('local')->get($chart->maidata_path))
        ->toContain('&lv_1=13')
        ->toContain('&des_1=New Charter');
});

test('the placeholder code can be replaced and is snapshotted in history', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->post(route('charts.difficulties.store', $chart), ['difficulty' => 'easy'])
        ->assertRedirect(route('charts.show', $chart));

    $difficulty = $chart->difficulties()->where('chart_index', '1')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), [
            'code' => '(179){1}1,2,3,',
        ])
        ->assertRedirect(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertSessionHas('status');

    expect($difficulty->fresh()->code)->toBe('(179){1}1,2,3,');

    $revisions = $difficulty->revisions()->get();

    expect($revisions)->toHaveCount(2)
        ->and($revisions->first()->code)->toBe('(179){1}1,2,3,')
        ->and($revisions->last()->code)->toBe(ChartDifficultyService::PLACEHOLDER_CODE)
        ->and($revisions->last()->is_original)->toBeTrue();

    expect(Storage::disk('local')->get($chart->maidata_path))
        ->toContain('&inote_1=(179){1}1,2,3,')
        ->not->toContain(ChartDifficultyService::PLACEHOLDER_CODE);
});

test('the details page offers only difficulties the chart does not have yet', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    $this->actingAs($owner)
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Add difficulty')
        ->assertSee('action="'.route('charts.difficulties.store', $chart).'"', false)
        ->assertSee('value="easy"', false)
        ->assertSee('value="remaster"', false)
        ->assertDontSee('value="master"', false)
        ->assertDontSee('value="expert"', false)
        ->assertDontSee('value="custom"', false);
});

test('a collaborator does not see the add form', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);
    $collaborator = User::factory()->create();
    $chart->collaborators()->attach($collaborator);

    $this->actingAs($collaborator)
        ->get(route('charts.show', $chart))
        ->assertOk()
        // The store URL is a prefix of the difficulty links, so assert the form itself.
        ->assertDontSee('Add difficulty');
});

test('a guest does not see the add form', function () {
    $owner = User::factory()->create();
    $chart = difficultyAddChart($owner);

    auth()->logout();

    $this->get(route('charts.show', $chart))
        ->assertOk()
        ->assertDontSee('Add difficulty');
});
