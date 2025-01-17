<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionCollaborator extends Model
{
    use HasFactory;

    protected $table = 'permission_collaborator';
    protected $primaryKey = ['collaborator_permission_id', 'permission_id'];
    public $incrementing = false; 

    protected $fillable = [
        'collaborator_user_id',
        'permission_id',
    ];

    public function collaborator_permission(){
        return $this->belongsTo(CollaboratorUser::class);
    }

    public function permission(){
        return $this->belongsTo(Permission::class);
    }
}
