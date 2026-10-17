<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    protected $fillable = [
        'full_name',
        'business_name',
        'whatsapp',
        'email',
        'address',
        'district',
        'regency',
        'product_type',
        'product_description',
        'logo_path',
        'special_notes',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
