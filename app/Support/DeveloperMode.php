<?php

namespace App\Support;

class DeveloperMode
{
    public static function enabled(): bool
    {
        return app()->environment('local');
    }
}
