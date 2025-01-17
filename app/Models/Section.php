<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $table = 'section';

    protected $fillable = [
        'id',
        'cms_item_id',
        'section_title',
        'section_description',
        'url_header_image',
        'url_card_image',
        'section_type',
    ];

    public function cmsItem()
    {
        return $this->belongsTo(CmsItem::class);
    }

    public function entryes()
    {
        return $this->hasMany(Entry::class);
    }
}
