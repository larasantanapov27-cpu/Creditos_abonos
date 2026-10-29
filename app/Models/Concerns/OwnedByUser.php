<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait OwnedByUser
{
    protected static function bootOwnedByUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            $query->where($query->getModel()->qualifyColumn('user_id'), Auth::id() ?? 0);
        });

        static::creating(function ($model) {
            abort_unless(Auth::check(), 403);
            $model->user_id = Auth::id();
        });

        static::updating(function ($model) {
            abort_unless(Auth::check() && (int) $model->getOriginal('user_id') === Auth::id(), 404);
            $model->user_id = $model->getOriginal('user_id');
        });
    }
}
