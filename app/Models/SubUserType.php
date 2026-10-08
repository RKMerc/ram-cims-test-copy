<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubUserType extends Model
{
    protected $table = 'SubUserType';

    protected $primaryKey = 'Id';

    protected $fillable = ['UserTypeId', 'Name'];

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'UserTypeId', 'Id');
    }
}
