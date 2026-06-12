<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    
    protected $fillable = [
        'name',
        // add other fields as needed
    ];
    
    public $timestamps = false;
}