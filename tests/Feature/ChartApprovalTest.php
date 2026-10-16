<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot approve a chart', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->patch(route('charts.approval.approve', $chart))->assertRedirect(route('login'));
});

test('a regular member cannot approve a chart', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.approval.approve', $chart))
        ->assertNotFound();

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('an approver can approve a chart that is for review', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.approve', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->refresh()->status)->toBe(ChartStatus::Approved);
});

test('an approver can approve a completed chart', function () {
    $chart = Chart::factory()->status(ChartStatus::Completed)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.approve', $chart))
        ->assertRedirect();

    expect($chart->refresh()->status)->toBe(ChartStatus::Approved);
});

test('an approver cannot approve a chart that is still a work in progress', function () {
    $chart = Chart::factory()->status(ChartStatus::Wip)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.approve', $chart))
        ->assertSessionHasErrors('status');

    expect($chart->refresh()->status)->toBe(ChartStatus::Wip);
});

test('an approver cannot approve their own chart', function () {
    $approver = User::factory()->approver()->create();
    $chart = Chart::factory()->for($approver, 'uploader')->status(ChartStatus::ForReview)->create();

    $this->actingAs($approver)
        ->patch(route('charts.approval.approve', $chart))
        ->assertNotFound();

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('an approver can revert an approved chart to for review', function () {
    $chart = Chart::factory()->status(ChartStatus::Approved)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.revert', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('reverting an approved chart always sets it to for review', function () {
    $chart = Chart::factory()->status(ChartStatus::Approved)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.revert', $chart))
        ->assertRedirect();

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('an approver cannot revert a chart that is not approved', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.approval.revert', $chart))
        ->assertSessionHasErrors('status');

    expect($chart->refresh()->status)->toBe(ChartStatus::ForReview);
});

test('the Settings tab is a link for an approver on a chart they do not own', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->approver()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee(route('charts.settings', $chart));
});

test('an approver sees only the approval controls on a chart they do not own', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Approval')
        ->assertSee('Approve chart')
        ->assertDontSee('Chart details')
        ->assertDontSee('Delete chart');
});

test('an approver sees a revert control on an already approved chart', function () {
    $chart = Chart::factory()->status(ChartStatus::Approved)->create();

    $this->actingAs(User::factory()->approver()->create())
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Revert approval')
        ->assertDontSee('Revert to')
        ->assertDontSee('Approve chart');
});

test('a regular member cannot view the approval settings page', function () {
    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.settings', $chart))
        ->assertNotFound();
});

test('an approver sees a disabled approve button on their own chart', function () {
    $approver = User::factory()->approver()->create();
    $chart = Chart::factory()->for($approver, 'uploader')->create();

    $this->actingAs($approver)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Chart details')
        ->assertSee('approve your own chart');
});

test('a regular owner does not see the approve button on their own chart', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();

    $this->actingAs($owner)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertDontSee('Approve chart');
});
