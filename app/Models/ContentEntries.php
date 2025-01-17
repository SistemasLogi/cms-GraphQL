<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentEntries extends Model
{
    use HasFactory;

    protected $table = 'content_entries';

    protected $fillable = [
        'id',
        'entry_id',
        'content',
        'content_type',
        'element_order'
    ];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
}
