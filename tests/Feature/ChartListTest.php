<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the list page shows the upload call to action', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Upload chart');
});

test('the list page shows the charts owned by the user', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'My Owned Chart']);
    Chart::factory()->create(['title' => 'Someone Elses Chart']);

    $this->actingAs($user)
        ->get(route('list'))
        ->assertOk()
        ->assertSee('My Owned Chart')
        ->assertDontSee('Someone Elses Chart');
});

test('the list page shows a placeholder when the user has no charts', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('list'))
        ->assertOk()
        ->assertSee('Charts you own or can edit will be listed here.');
});
