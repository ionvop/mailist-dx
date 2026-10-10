<?php

use App\Models\Chart;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot view a chart\'s comments', function () {
    $chart = Chart::factory()->create();

    $this->get(route('charts.comments.index', $chart))->assertRedirect(route('login'));
});

test('a user can view a chart\'s comments', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create(['body' => 'Great chart!']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.comments.index', $chart))
        ->assertOk()
        ->assertSee('Great chart!')
        ->assertSee($comment->user->username);
});

test('a user can post a comment on a chart', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('charts.comments.store', $chart), ['body' => 'Nice work!'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $comment = Comment::firstOrFail();
    expect($comment->chart_id)->toBe($chart->id);
    expect($comment->user_id)->toBe($user->id);
    expect($comment->body)->toBe('Nice work!');
});

test('a comment requires a body', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.comments.store', $chart), ['body' => ''])
        ->assertSessionHasErrors('body');

    expect(Comment::count())->toBe(0);
});

test('a guest cannot post a comment', function () {
    $chart = Chart::factory()->create();

    $this->post(route('charts.comments.store', $chart), ['body' => 'Hello'])
        ->assertRedirect(route('login'));

    expect(Comment::count())->toBe(0);
});

test('the author can delete their own comment', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->for($user)->create();

    $this->actingAs($user)
        ->delete(route('charts.comments.destroy', [$chart, $comment]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Comment::count())->toBe(0);
});

test('a user cannot delete someone else\'s comment', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.comments.destroy', [$chart, $comment]))
        ->assertForbidden();

    expect(Comment::count())->toBe(1);
});

test('a non-owner cannot view a private chart\'s comments', function () {
    $chart = Chart::factory()->private()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('charts.comments.index', $chart))
        ->assertNotFound();
});

test('a non-owner cannot comment on a private chart', function () {
    $chart = Chart::factory()->private()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('charts.comments.store', $chart), ['body' => 'Sneaky'])
        ->assertNotFound();

    expect(Comment::count())->toBe(0);
});

test('the owner can comment on their private chart', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->private()->create();

    $this->actingAs($owner)
        ->post(route('charts.comments.store', $chart), ['body' => 'My own note'])
        ->assertRedirect();

    expect(Comment::count())->toBe(1);
});
