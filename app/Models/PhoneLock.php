<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneLock extends Model
{
    protected $primaryKey = 'phone';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['phone'];
}
