<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permission';

    protected $fillable = [
        'id',
        'permission_name',
        'access_level',
    ];

    public function collaborator_permission(){
        return $this->hasMany(PermissionCollaborator::class);
    }
}
