<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroserviceAuthentication;

use Exception;
use App\Models\CollaboratorUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

final class RefreshTokenCollaborator
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {
            // Desencriptar los datos del encryptedKey
            $decryptedData = Crypt::decryptString($args['encryptedKey']);

            // Decodificar el JSON a un array
            $data = json_decode($decryptedData, true);

            // Buscar al usuario colaborador por su ID
            $collaboratorUser = CollaboratorUser::find($data['collaborator_user_id']);

            // Validar si el usuario fue encontrado
            if ($collaboratorUser) {

                // Validar si el usuario está activo
                if ($collaboratorUser->collaborator_status !== 1) {
                    return $this->formatResponse('NOT OK', 403, 'Usuario inactivo en el sistema');
                }

                // Generar un nuevo token para el usuario colaborador
                $newToken = Auth::claims($data)->fromUser($collaboratorUser);

                // Obtener permisos del usuario colaborador
                $permissions = $collaboratorUser->permissionCollaborator->pluck('permission_id')->toArray();

                // Devolver respuesta exitosa con el nuevo token
                return $this->formatResponse('OK', 200, 'Usuario autenticado', [
                    'accessToken' => $newToken,
                    'expiresIn' => 3600,
                    'measureTime' => 'seconds',
                    'accessType' => 'collaborator',
                    'permissions' => $permissions,
                ]);
            } else {
                // Usuario no encontrado
                return $this->formatResponse('NOT OK', 422, 'Usuario no encontrado');
            }
        } catch (Exception $e) {
            // Manejo de errores generales
            return $this->formatResponse('NOT OK', 500, 'Error al refrescar el token: ' . $e->getMessage());
        }
    }

    private function formatResponse($status, $statusCode, $statusMessage, $data = [])
    {
        return array_merge([
            'status' => $status,
            'status_code' => $statusCode,
            'status_message' => $statusMessage,
            'accessToken' => null,
            'expiresIn' => null,
            'measureTime' => null,
            'accessType' => null,
            'encryptedKey' => null,
            'permissions' => [],
        ], $data);
    }
}
