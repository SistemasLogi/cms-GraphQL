<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;

    protected $table = 'entry';

    protected $fillable = ['id', 'section_id', 'entry_title'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function contentEntries()
    {
        return $this->hasMany(ContentEntries::class);
    }

    public function fileEntries(){
        return $this->hasMany(FileEntries::class);
    }
}
