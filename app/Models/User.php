<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // ek-core does not own the users table. This model is a non-persisted carrier for the
    // identity that arrives, already verified, in the token / session. It is never saved.
    protected $guarded = [];

    // No DB connection backs it, so Eloquent must not try to touch one.
    protected static function booted(): void
    {
        static::saving(fn() => throw new \LogicException('ek-core User is read-only.'));
    }

    public $timestamps = false;

    protected $hidden = ['password', 'remember_token'];
}