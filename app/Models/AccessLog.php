<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'description',
        'model_type',
        'model_id',
        'event',
        'payload',
        'changes',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'changes' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to the affected model
     */
    public function auditable()
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }
}