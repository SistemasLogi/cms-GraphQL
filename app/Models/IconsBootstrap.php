<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IconsBootstrap extends Model
{
    use HasFactory;

    protected $table = 'icons_bootstrap';

    protected $fillable = [
        'id',
        'icon_class',
        'icon_name'
    ];
}
