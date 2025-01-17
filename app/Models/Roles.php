<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;
    protected $table = 'roles';

    protected $fillable = [
        'id',
        'rol_name'
    ];

    public function collaborator_user(){
        return $this->hasMany(CollaboratorUser::class);
    }
}
