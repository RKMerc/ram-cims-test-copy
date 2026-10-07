<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    protected $table = 'UserType';

    protected $primaryKey = 'Id';

    protected $fillable = ['Name'];

    public function users(): HasMany
    {
        return $this->hasMany(AppUser::class, 'UserTypeId', 'Id');
    }

    public function subTypes(): HasMany
    {
        return $this->hasMany(SubUserType::class, 'UserTypeId', 'Id');
    }
}
