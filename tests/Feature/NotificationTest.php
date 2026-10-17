<?php

use App\Enums\ChartStatus;
use App\Models\Chart;
use App\Models\ChartDifficulty;
use App\Models\ChartDifficultyImprovement;
use App\Models\User;
use App\Notifications\ChartCommentCreated;
use App\Notifications\ChartDifficultyCommentCreated;
use App\Notifications\ChartStatusChanged;
use App\Notifications\ImprovementResolved;
use App\Notifications\ImprovementSuggested;
use App\Notifications\ProfileCommentCreated;
use App\Notifications\UserFollowed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Create a difficulty (owned by the given user) with an initial code value.
 */
function notificationDifficultyFor(?User $owner = null): ChartDifficulty
{
    $owner ??= User::factory()->create();

    return ChartDifficulty::factory()
        ->for(Chart::factory()->for($owner, 'uploader'))
        ->create(['code' => "1,2,3,\n4,5,6,\n7,8,9,"]);
}

/*
|--------------------------------------------------------------------------
| Follow
|--------------------------------------------------------------------------
*/

test('following a user notifies them', function () {
    Notification::fake();

    $follower = User::factory()->create();
    $followed = User::factory()->create();

    $this->actingAs($follower)
        ->post(route('users.follow.store', $followed))
        ->assertRedirect();

    Notification::assertSentTo($followed, UserFollowed::class);
});

test('following a user twice only notifies once', function () {
    Notification::fake();

    $follower = User::factory()->create();
    $followed = User::factory()->create();

    $this->actingAs($follower)->post(route('users.follow.store', $followed))->assertRedirect();
    $this->actingAs($follower)->post(route('users.follow.store', $followed))->assertRedirect();

    Notification::assertSentToTimes($followed, UserFollowed::class, 1);
});

/*
|--------------------------------------------------------------------------
| Profile comment
|--------------------------------------------------------------------------
*/

test('commenting on a profile notifies the profile owner', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)
        ->post(route('users.comments.store', $owner), ['body' => 'Nice profile!'])
        ->assertRedirect();

    Notification::assertSentTo($owner, ProfileCommentCreated::class);
});

test('commenting on your own profile does not notify you', function () {
    Notification::fake();

    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('users.comments.store', $owner), ['body' => 'Note to self'])
        ->assertRedirect();

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Chart comment
|--------------------------------------------------------------------------
*/

test('commenting on a chart notifies the uploader', function () {
    Notification::fake();

    $chart = Chart::factory()->create();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)
        ->post(route('charts.comments.store', $chart), ['body' => 'Great chart!'])
        ->assertRedirect();

    Notification::assertSentTo($chart->uploader, ChartCommentCreated::class);
});

test('commenting on your own chart does not notify you', function () {
    Notification::fake();

    $chart = Chart::factory()->create();

    $this->actingAs($chart->uploader)
        ->post(route('charts.comments.store', $chart), ['body' => 'Self note'])
        ->assertRedirect();

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Difficulty comment
|--------------------------------------------------------------------------
*/

test('commenting on a difficulty notifies the chart uploader', function () {
    Notification::fake();

    $difficulty = notificationDifficultyFor();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)
        ->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => 'Sharp!'])
        ->assertRedirect();

    Notification::assertSentTo($difficulty->chart->uploader, ChartDifficultyCommentCreated::class);
});

test('commenting on your own difficulty does not notify you', function () {
    Notification::fake();

    $difficulty = notificationDifficultyFor();

    $this->actingAs($difficulty->chart->uploader)
        ->post(route('charts.difficulties.comments.store', [$difficulty->chart, $difficulty]), ['body' => 'Self note'])
        ->assertRedirect();

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Improvement suggested
|--------------------------------------------------------------------------
*/

test('suggesting an improvement notifies the chart uploader', function () {
    Notification::fake();

    $difficulty = notificationDifficultyFor();
    $suggester = User::factory()->create();

    $this->actingAs($suggester)
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'These notes do not match the song.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($difficulty->chart->uploader, ImprovementSuggested::class);
});

test('suggesting an improvement on your own chart does not notify you', function () {
    Notification::fake();

    $difficulty = notificationDifficultyFor();

    $this->actingAs($difficulty->chart->uploader)
        ->post(route('charts.difficulties.improvements.store', [$difficulty->chart, $difficulty]), [
            'body' => 'Self note',
        ])
        ->assertRedirect();

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Improvement resolved / rejected
|--------------------------------------------------------------------------
*/

test('resolving a suggestion notifies the suggester', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $difficulty = notificationDifficultyFor($owner);
    $suggester = User::factory()->create();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['user_id' => $suggester->id]);

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => 'Fixed in the latest revision.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($suggester, ImprovementResolved::class);
});

test('rejecting a suggestion notifies the suggester', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $difficulty = notificationDifficultyFor($owner);
    $suggester = User::factory()->create();
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['user_id' => $suggester->id]);

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'rejected',
            'resolution_note' => 'Working as intended.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($suggester, ImprovementResolved::class);
});

test('resolving your own suggestion does not notify you', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $difficulty = notificationDifficultyFor($owner);
    $improvement = ChartDifficultyImprovement::factory()->for($difficulty, 'difficulty')->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->patch(route('charts.difficulties.improvements.update', [$difficulty->chart, $difficulty, $improvement]), [
            'status' => 'resolved',
            'resolution_note' => 'Fixed it myself.',
        ])
        ->assertRedirect();

    Notification::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Chart status change
|--------------------------------------------------------------------------
*/

test('approving a chart notifies the uploader', function () {
    Notification::fake();

    $chart = Chart::factory()->status(ChartStatus::ForReview)->create();
    $approver = User::factory()->approver()->create();

    $this->actingAs($approver)
        ->patch(route('charts.approval.approve', $chart))
        ->assertRedirect();

    Notification::assertSentTo($chart->uploader, ChartStatusChanged::class);
});

test('reverting an approval notifies the uploader', function () {
    Notification::fake();

    $chart = Chart::factory()->status(ChartStatus::Approved)->create();
    $approver = User::factory()->approver()->create();

    $this->actingAs($approver)
        ->patch(route('charts.approval.revert', $chart))
        ->assertRedirect();

    Notification::assertSentTo($chart->uploader, ChartStatusChanged::class);
});

/*
|--------------------------------------------------------------------------
| Notifications page
|--------------------------------------------------------------------------
*/

test('the notifications page lists stored notifications', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create(['username' => 'loyal_fan']);

    $user->notify(new UserFollowed($follower->username));

    $this->actingAs($user)
        ->get(route('notifications'))
        ->assertOk()
        ->assertSee('New follower')
        ->assertSee('loyal_fan started following you.');
});

test('the notifications page shows an empty state', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications'))
        ->assertOk()
        ->assertSee('No notifications yet');
});

test('the header shows the unread notification count', function () {
    $user = User::factory()->create();

    $user->notify(new UserFollowed('first_fan'));
    $user->notify(new UserFollowed('second_fan'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('2 unread notifications');
});

test('the header hides the badge when everything is read', function () {
    $user = User::factory()->create();

    $user->notify(new UserFollowed('quiet_fan'));
    $user->unreadNotifications()->update(['read_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('unread notifications');
});

test('marking all notifications as read clears the badge', function () {
    $user = User::factory()->create();

    $user->notify(new UserFollowed('a_fan'));
    $user->notify(new UserFollowed('another_fan'));

    $this->actingAs($user)
        ->patch(route('notifications.readAll'))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('opening a notification marks it read and continues to its target', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)
        ->post(route('users.comments.store', $owner), ['body' => 'Hello there'])
        ->assertRedirect();

    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('notifications.read', $notification))
        ->assertRedirect(route('users.show', $owner));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another user\'s notification as read', function () {
    $owner = User::factory()->create();
    $commenter = User::factory()->create();

    $owner->notify(new UserFollowed('someone'));

    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($commenter)
        ->patch(route('notifications.read', $notification))
        ->assertNotFound();

    expect($notification->refresh()->read_at)->toBeNull();
});

test('a guest cannot mark notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new UserFollowed('a_fan'));

    $notification = $user->notifications()->firstOrFail();

    $this->patch(route('notifications.read', $notification))
        ->assertRedirect(route('login'));

    $this->patch(route('notifications.readAll'))
        ->assertRedirect(route('login'));
});
