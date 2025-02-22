<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileEntries extends Model
{
    use HasFactory;

    protected $table = 'file_entries';

    protected $fillable = [
        'id',
        'entry_id',
        'url_file',
        'file_type',
        'element_order',
        'orientation_img',
    ];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
}
