<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsItem extends Model
{
    use HasFactory;

    protected $table = 'cms_item';

    protected $fillable = [
        'id',
        'cms_item_name',
        'cms_item_title',
        'url_header_image',
        'text_add'
    ];

    public function sections(){    
        return $this->hasMany(Section::class);
    }
}
