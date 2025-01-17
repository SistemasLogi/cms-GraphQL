<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CollaboratorUser extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'collaborator_user';

    protected $fillable = [
        'id',
        'roles_id',
        'document_number',
        'collaborator_name',
        'collaborator_email',
        'collaborator_status',
        'user',
        'password',
    ];

    public function roles()
    {
        return $this->belongsTo(Roles::class);
    }

    public function permissionCollaborator()
    {
        return $this->hasMany(PermissionCollaborator::class, 'collaborator_user_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {

        $roleName = $this->roles()->value('rol_name');

        return [
            'collaborator_id' => $this->id,
            'user' => $this->user,
            'role' => $roleName,
        ];
    }
}
