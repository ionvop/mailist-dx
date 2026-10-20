<?php

use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyComment;
use App\Models\ChartDifficultyImprovement;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a moderator can delist a chart', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->patch(route('charts.moderation.delist', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->fresh()->is_delisted)->toBeTrue();
});

test('a moderator can restore a delisted chart', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->actingAs(User::factory()->moderator()->create())
        ->patch(route('charts.moderation.restore', $chart))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($chart->fresh()->is_delisted)->toBeFalse();
});

test('a non-moderator cannot delist a chart', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('charts.moderation.delist', $chart))
        ->assertNotFound();

    expect($chart->fresh()->is_delisted)->toBeFalse();
});

test('the Settings tab is a link for a moderator on a chart they do not own', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('charts.show', $chart))
        ->assertOk()
        ->assertSee(route('charts.settings', $chart));
});

test('a moderator sees only the moderation controls on a chart they do not own', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Moderation')
        ->assertSee('Delist chart')
        ->assertDontSee('Chart details')
        ->assertDontSee('Delete chart');
});

test('a moderator sees a restore control on a delisted chart', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Restore chart')
        ->assertDontSee('Delist chart');
});

test('a moderator who is also a collaborator sees both moderation and leave controls', function () {
    $chart = Chart::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $chart->collaborators()->attach($moderator->id);

    $this->actingAs($moderator)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Delist chart')
        ->assertSee('Remove my edit access')
        ->assertDontSee('Delete chart');
});

test('a moderator sees the owner settings on their own chart', function () {
    $moderator = User::factory()->moderator()->create();
    $chart = Chart::factory()->for($moderator, 'uploader')->create();

    $this->actingAs($moderator)
        ->get(route('charts.settings', $chart))
        ->assertOk()
        ->assertSee('Chart details')
        ->assertDontSee('Delist chart');
});

test('an approver cannot delist a chart', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->approver()->create())
        ->patch(route('charts.moderation.delist', $chart))
        ->assertNotFound();

    expect($chart->fresh()->is_delisted)->toBeFalse();
});

test('a moderator cannot delist their own chart', function () {
    $moderator = User::factory()->moderator()->create();
    $chart = Chart::factory()->create(['uploader_id' => $moderator->id]);

    $this->actingAs($moderator)
        ->patch(route('charts.moderation.delist', $chart))
        ->assertNotFound();

    expect($chart->fresh()->is_delisted)->toBeFalse();
});

test('a guest cannot delist a chart', function () {
    $chart = Chart::factory()->create();

    $this->patch(route('charts.moderation.delist', $chart))
        ->assertRedirect(route('login'));

    expect($chart->fresh()->is_delisted)->toBeFalse();
});

test('delisting a chart notifies the owner', function () {
    $chart = Chart::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->patch(route('charts.moderation.delist', $chart));

    expect($chart->uploader->notifications()->count())->toBe(1);
    expect($chart->uploader->notifications()->first()->data['title'])->toBe('Chart delisted');
});

test('restoring a chart notifies the owner', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->actingAs(User::factory()->moderator()->create())
        ->patch(route('charts.moderation.restore', $chart));

    expect($chart->uploader->notifications()->count())->toBe(1);
    expect($chart->uploader->notifications()->first()->data['title'])->toBe('Chart restored');
});

test('a delisted chart is hidden from guests and other users', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->get(route('charts.show', $chart))->assertNotFound();
    $this->actingAs(User::factory()->create())
        ->get(route('charts.show', $chart))
        ->assertNotFound();
});

test('a delisted chart is still visible to its owner and moderators', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->actingAs($chart->uploader)
        ->get(route('charts.show', $chart))
        ->assertOk();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('charts.show', $chart))
        ->assertOk();
});

test('delisted charts are excluded from public listings', function () {
    $delisted = Chart::factory()->create(['is_delisted' => true, 'title' => 'Delisted Chart']);
    Chart::factory()->create(['title' => 'Visible Chart']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Delisted Chart')
        ->assertSee('Visible Chart');

    $this->get(route('search', ['q' => 'Delisted']))
        ->assertOk()
        ->assertDontSee('Delisted Chart');
});

test('the chart owner cannot undelist their chart via visibility settings', function () {
    $chart = Chart::factory()->create(['is_delisted' => true]);

    $this->actingAs($chart->uploader)
        ->patch(route('charts.settings.visibility', $chart), ['is_private' => false])
        ->assertRedirect();

    expect($chart->fresh()->is_delisted)->toBeTrue();
});

test('a moderator can delete another user\'s chart comment', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('charts.comments.destroy', [$chart, $comment]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Comment::count())->toBe(0);
});

test('a non-moderator cannot delete another user\'s chart comment', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.comments.destroy', [$chart, $comment]))
        ->assertForbidden();

    expect(Comment::count())->toBe(1);
});

test('removing a chart comment notifies its author', function () {
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('charts.comments.destroy', [$chart, $comment]));

    expect($comment->user->notifications()->count())->toBe(1);
    expect($comment->user->notifications()->first()->data['title'])->toBe('Comment removed');
});

test('a moderator can delete another user\'s profile comment', function () {
    $profileUser = User::factory()->create();
    $comment = UserComment::factory()->for($profileUser, 'profileUser')->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('users.comments.destroy', [$profileUser, $comment]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(UserComment::count())->toBe(0);
});

test('a non-moderator cannot delete another user\'s profile comment', function () {
    $profileUser = User::factory()->create();
    $comment = UserComment::factory()->for($profileUser, 'profileUser')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('users.comments.destroy', [$profileUser, $comment]))
        ->assertForbidden();

    expect(UserComment::count())->toBe(1);
});

test('a moderator can delete another user\'s difficulty comment', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $comment = ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('charts.difficulties.comments.destroy', [$chart, $difficulty, $comment]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ChartDifficultyComment::count())->toBe(0);
});

test('a non-moderator cannot delete another user\'s difficulty comment', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $comment = ChartDifficultyComment::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.difficulties.comments.destroy', [$chart, $difficulty, $comment]))
        ->assertForbidden();

    expect(ChartDifficultyComment::count())->toBe(1);
});

test('a moderator can delete another user\'s improvement suggestion', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('charts.difficulties.improvements.destroy', [$chart, $difficulty, $improvement]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ChartDifficultyImprovement::count())->toBe(0);
});

test('a non-moderator cannot delete another user\'s improvement suggestion', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('charts.difficulties.improvements.destroy', [$chart, $difficulty, $improvement]))
        ->assertForbidden();

    expect(ChartDifficultyImprovement::count())->toBe(1);
});

test('removing an improvement suggestion notifies its author', function () {
    $chart = Chart::factory()->create();
    $difficulty = ChartDifficulty::factory()->for($chart)->create();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('charts.difficulties.improvements.destroy', [$chart, $difficulty, $improvement]));

    expect($improvement->user->notifications()->count())->toBe(1);
    expect($improvement->user->notifications()->first()->data['title'])->toBe('Suggestion removed');
});

test('a user can still delete their own comment as before', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create();
    $comment = Comment::factory()->for($chart)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('charts.comments.destroy', [$chart, $comment]))
        ->assertRedirect();

    expect(Comment::count())->toBe(0);
    expect($comment->user->notifications()->count())->toBe(0);
});
