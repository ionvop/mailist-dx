<?php

use App\Models\Chart;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Bio
|--------------------------------------------------------------------------
*/

test('the profile page renders the bio as markdown', function () {
    $user = User::factory()->create(['bio' => 'I make **maimai** charts.']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('<strong>maimai</strong>', false);
});

test('the profile page shows a placeholder when the bio is empty', function () {
    $user = User::factory()->create(['bio' => null]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('This user has not written a bio yet.');
});

test('the profile page strips raw html from the bio', function () {
    $user = User::factory()->create(['bio' => 'Hello <script>alert(1)</script> world']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee('<script>', false);
});

test('a guest sees the bio placeholder', function () {
    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('This user has not written a bio yet.');
});

/*
|--------------------------------------------------------------------------
| Public profiles
|--------------------------------------------------------------------------
*/

test('a guest can view a user profile by username', function () {
    $user = User::factory()->create(['username' => 'chartmaker', 'bio' => 'Hello there']);

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertSee('chartmaker')
        ->assertSee('Hello there');
});

test('a public profile hides the email of another user', function () {
    $user = User::factory()->create(['username' => 'otherguy', 'email' => 'secret@example.com']);

    $this->actingAs(User::factory()->create())
        ->get(route('users.show', $user))
        ->assertOk()
        ->assertDontSee('secret@example.com');
});

test('an unknown username returns not found', function () {
    $this->get('/users/does-not-exist')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Charts uploaded and liked
|--------------------------------------------------------------------------
*/

test('the profile page shows the charts uploaded by the user', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create(['title' => 'My Uploaded Chart']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('My Uploaded Chart');
});

test('the profile page shows the charts the user has liked', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create(['title' => 'A Liked Chart']);
    $chart->likes()->attach($user);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('A Liked Chart');
});

test('the profile page hides private charts from the uploaded and liked sections', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->private()->create(['title' => 'My Private Upload']);
    $liked = Chart::factory()->private()->create(['title' => 'A Private Liked Chart']);
    $liked->likes()->attach($user);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee('My Private Upload')
        ->assertDontSee('A Private Liked Chart');
});

test('the profile page links to the dedicated uploaded and liked pages', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->create();
    $liked = Chart::factory()->create();
    $liked->likes()->attach($user);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee(route('users.charts', $user))
        ->assertSee(route('users.liked', $user));
});

test('the profile page shows empty placeholders when there are no charts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('No charts uploaded')
        ->assertSee('No liked charts');
});

/*
|--------------------------------------------------------------------------
| Dedicated uploaded and liked pages
|--------------------------------------------------------------------------
*/

test('a guest can view a user uploaded charts page', function () {
    $user = User::factory()->create(['username' => 'chartmaker']);
    Chart::factory()->for($user, 'uploader')->create(['title' => 'Public Upload']);

    $this->get(route('users.charts', $user))
        ->assertOk()
        ->assertSee('Public Upload');
});

test('the uploaded charts page hides private charts', function () {
    $user = User::factory()->create();
    Chart::factory()->for($user, 'uploader')->private()->create(['title' => 'Secret Upload']);

    $this->actingAs($user)
        ->get(route('users.charts', $user))
        ->assertOk()
        ->assertDontSee('Secret Upload');
});

test('the liked charts page shows the charts the user has liked', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->create(['title' => 'Liked By User']);
    $chart->likes()->attach($user);

    $this->get(route('users.liked', $user))
        ->assertOk()
        ->assertSee('Liked By User');
});

test('the liked charts page hides private charts', function () {
    $user = User::factory()->create();
    $chart = Chart::factory()->private()->create(['title' => 'Private Liked']);
    $chart->likes()->attach($user);

    $this->get(route('users.liked', $user))
        ->assertOk()
        ->assertDontSee('Private Liked');
});

/*
|--------------------------------------------------------------------------
| Profile comments
|--------------------------------------------------------------------------
*/

test('the profile page shows the comments left on the profile', function () {
    $user = User::factory()->create();
    UserComment::factory()->for($user, 'profileUser')->create(['body' => 'Great charts!']);

    $this->get(route('users.show', $user))
        ->assertOk()
        ->assertSee('Great charts!');
});

test('an authenticated user can comment on a profile', function () {
    $user = User::factory()->create();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)
        ->post(route('users.comments.store', $user), ['body' => 'Nice profile.'])
        ->assertRedirect();

    expect($user->receivedProfileComments()->count())->toBe(1);
    expect($user->receivedProfileComments()->first()->body)->toBe('Nice profile.');
});

test('a user can comment on their own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.comments.store', $user), ['body' => 'Hello me.'])
        ->assertRedirect();

    expect($user->receivedProfileComments()->count())->toBe(1);
});

test('a guest cannot comment on a profile', function () {
    $user = User::factory()->create();

    $this->post(route('users.comments.store', $user), ['body' => 'Sneaky.'])
        ->assertRedirect(route('login'));

    expect($user->receivedProfileComments()->count())->toBe(0);
});

test('a comment body is required', function () {
    $user = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('users.comments.store', $user), ['body' => ''])
        ->assertSessionHasErrors('body');
});

test('the author can delete their own profile comment', function () {
    $user = User::factory()->create();
    $commenter = User::factory()->create();
    $comment = UserComment::factory()->for($user, 'profileUser')->for($commenter)->create();

    $this->actingAs($commenter)
        ->delete(route('users.comments.destroy', [$user, $comment]))
        ->assertRedirect();

    expect(UserComment::find($comment->id))->toBeNull();
});

test('a user cannot delete another users profile comment', function () {
    $user = User::factory()->create();
    $comment = UserComment::factory()->for($user, 'profileUser')->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('users.comments.destroy', [$user, $comment]))
        ->assertForbidden();

    expect(UserComment::find($comment->id))->not->toBeNull();
});

test('a comment cannot be deleted through the wrong profile', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $comment = UserComment::factory()->for($user, 'profileUser')->create();

    $this->actingAs($comment->user)
        ->delete(route('users.comments.destroy', [$other, $comment]))
        ->assertNotFound();
});
