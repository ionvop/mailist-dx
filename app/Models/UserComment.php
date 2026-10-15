<?php

namespace App\Models;

use Database\Factories\UserCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_user_id', 'user_id', 'body'])]
class UserComment extends Model
{
    /** @use HasFactory<UserCommentFactory> */
    use HasFactory;

    /**
     * The user whose profile the comment was left on.
     *
     * @return BelongsTo<User, $this>
     */
    public function profileUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    /**
     * The user who wrote the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
