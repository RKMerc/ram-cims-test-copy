<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicNotification extends Model
{
    protected $fillable = [
        'item_code',
        'kind',
        'title',
        'body',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }
}
