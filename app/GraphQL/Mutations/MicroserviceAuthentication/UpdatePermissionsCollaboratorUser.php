<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroserviceAuthentication;

use Exception;
use App\Models\CollaboratorUser;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\PermissionCollaborator;

final class UpdatePermissionsCollaboratorUser
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {
            return DB::transaction(function () use ($args) {
                $token = JWTAuth::parseToken();
                $payload = $token->getPayload();
                $role = $payload->get('role');
                $permissions = $args['permissions'];
                $userId = $args['user_id'];

                // Validar rol de administrador
                if ($role !== 'Administrador') {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Verificar si el usuario existe
                $user = CollaboratorUser::find($userId);
                if (!$user) {
                    return $this->formatResponse('NOT OK', 404, 'Usuario no encontrado');
                }

                // Verificar si el usuario es Administrador (roles_id = 1)
                if ($user->roles_id === 1) {
                    return $this->formatResponse('NOT OK', 403, 'No se pueden modificar los permisos de un administrador');
                }

                // Validar y actualizar permisos para Publicador (roles_id = 2)
                if ($user->roles_id === 2) {
                    // Garantizar que el permiso 1 siempre esté presente
                    if (!in_array(1, $permissions)) {
                        $permissions[] = 1;
                    }

                    // Eliminar permisos no incluidos en el arreglo, excepto el permiso 1
                    PermissionCollaborator::where('collaborator_user_id', $userId)
                        ->whereNotIn('permission_id', $permissions)
                        ->where('permission_id', '!=', 1)
                        ->delete();

                    // Agregar nuevos permisos
                    foreach ($permissions as $permissionId) {
                        PermissionCollaborator::firstOrCreate([
                            'collaborator_user_id' => $userId,
                            'permission_id' => $permissionId,
                        ]);
                    }

                    // Cargar el modelo con sus relaciones actualizadas
                    $user->load(['roles', 'permissionCollaborator']);

                    return $this->formatResponse('OK', 200, 'Permisos actualizados correctamente', [
                        'collaborator_user' => $user
                    ]);
                }

                // Si el rol no es válido
                return $this->formatResponse('NOT OK', 403, 'Rol no autorizado para actualizar permisos');
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Estructura estándar de respuesta.
     */
    private function formatResponse($status, $statusCode, $statusMessage, $data = [])
    {
        return array_merge([
            'status' => $status,
            'status_code' => $statusCode,
            'status_message' => $statusMessage,
            'collaborator_user' => null, // Clave garantizada siempre presente
        ], $data);
    }
}
