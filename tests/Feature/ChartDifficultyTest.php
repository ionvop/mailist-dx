<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a guest can view a public chart difficulty', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create([
        'code' => '(179){1}4,5,6,',
    ]);

    $this->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertOk()
        ->assertSee('Code')
        ->assertSee('(179){1}4,5,6,', false);
});

test('a guest cannot view a difficulty of a private chart', function () {
    $chart = Chart::factory()->private()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertNotFound();
});

test('a user can view a public chart difficulty and its code', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create([
        'code' => '(179){1}4,5,6,',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertOk()
        ->assertSee('Code')
        ->assertSee('(179){1}4,5,6,', false);
});

test('the code tab is shown by default and unknown tabs fall back to it', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty, 'nonsense']))
        ->assertOk()
        ->assertSee('Code');
});

test('a non-editor sees the code read-only', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertOk()
        ->assertDontSee('Save changes')
        ->assertSee('You need edit access', false)
        // The textarea is still rendered (so the caret can be placed) but is
        // read-only and not submitted.
        ->assertSee('class="simai-input"', false)
        ->assertSee('readonly', false)
        ->assertDontSee('name="code"', false);
});

test('the code editor shows a cursor time readout', function () {
    $chart = Chart::factory()->create(['audio_bpm' => '179', 'audio_offset' => '0.5']);
    $difficulty = ChartDifficulty::factory()->for($chart)->create([
        'code' => '(179){1}4,5,6,',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertOk()
        ->assertSee('simai-status', false)
        ->assertSee('timeLabel', false);
});

test('the owner can update a difficulty code', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create([
        'maidata_path' => 'charts/1/maidata.txt',
    ]);
    Storage::disk('local')->put('charts/1/maidata.txt', "&title=Song\n&inote_2=1,2,3,\n&lv_2=5\n");

    $difficulty = ChartDifficulty::factory()->for($chart)->create([
        'chart_index' => '2',
        'code' => '1,2,3,',
    ]);

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), [
            'code' => "4,5,6,\n7,8,9,",
        ])
        ->assertRedirect(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertSessionHas('status');

    expect($difficulty->fresh()->code)->toBe("4,5,6,\n7,8,9,");

    $maidata = Storage::disk('local')->get('charts/1/maidata.txt');

    expect($maidata)
        ->toContain("&inote_2=4,5,6,\n7,8,9,")
        ->toContain('&lv_2=5');
});

test('a collaborator can update a difficulty code', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $chart->collaborators()->attach($collaborator);

    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->actingAs($collaborator)
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), [
            'code' => '1,2,3,',
        ])
        ->assertRedirect(route('charts.difficulties.show', [$chart, $difficulty]));

    expect($difficulty->fresh()->code)->toBe('1,2,3,');
});

test('a non-editor cannot update a difficulty code', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create(['code' => '1,2,3,']);

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), [
            'code' => '9,9,9,',
        ])
        ->assertNotFound();

    expect($difficulty->fresh()->code)->toBe('1,2,3,');
});

test('a difficulty from another chart is not found', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for(Chart::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertNotFound();
});

test('a non-owner cannot view a difficulty of a private chart', function () {
    $chart = Chart::factory()->private()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.difficulties.show', [$chart, $difficulty]))
        ->assertNotFound();
});

test('code is required and may not contain maidata keys', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), ['code' => ''])
        ->assertSessionHasErrors('code');

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.update', [$chart, $difficulty]), ['code' => '&title=Evil'])
        ->assertSessionHasErrors('code');
});
