<?php

use App\Models\Chart;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest cannot post a comment', function () {
    $chart = Chart::factory()->create();

    $this->post(route('comments.store', $chart), ['body' => 'Hello'])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('comments', 0);
});

test('a user can post a comment on a chart', function () {
    $chart = Chart::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('comments.store', $chart), ['body' => 'Great chart!'])
        ->assertRedirect(route('charts.show', $chart).'#comments');

    $this->assertDatabaseHas('comments', [
        'chart_id' => $chart->id,
        'user_id' => $user->id,
        'body' => 'Great chart!',
    ]);
});

test('a comment body is required', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $chart), ['body' => ''])
        ->assertSessionHasErrors('body');

    $this->assertDatabaseCount('comments', 0);
});

test('a comment body may not exceed 2000 characters', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $chart), ['body' => str_repeat('a', 2001)])
        ->assertSessionHasErrors('body');

    $this->assertDatabaseCount('comments', 0);
});

test('comments are shown on the chart page', function () {
    $chart = Chart::factory()->create();
    $author = User::factory()->create(['username' => 'Commenter']);
    Comment::factory()->for($chart)->for($author, 'author')->create(['body' => 'Nice work here']);

    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee('Commenter')
        ->assertSee('Nice work here');
});

test('the author can delete their own comment', function () {
    $chart = Chart::factory()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->for($chart)->for($author, 'author')->create();

    $this->actingAs($author)
        ->delete(route('comments.destroy', [$chart, $comment]))
        ->assertRedirect(route('charts.show', $chart).'#comments');

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

test('the chart owner can delete a comment', function () {
    $owner = User::factory()->create();
    $chart = Chart::factory()->for($owner, 'uploader')->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs($owner)
        ->delete(route('comments.destroy', [$chart, $comment]))
        ->assertRedirect(route('charts.show', $chart).'#comments');

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

test('an unrelated user cannot delete a comment', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('comments.destroy', [$chart, $comment]))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

test('a user cannot comment on a private chart they cannot see', function () {
    $chart = Chart::factory()->private()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $chart), ['body' => 'Sneaky'])
        ->assertNotFound();

    $this->assertDatabaseCount('comments', 0);
});

test('a comment cannot be deleted through a different chart', function () {
    $chart = Chart::factory()->create();
    $otherChart = Chart::factory()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->for($chart)->for($author, 'author')->create();

    $this->actingAs($author)
        ->delete(route('comments.destroy', [$otherChart, $comment]))
        ->assertNotFound();

    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});
