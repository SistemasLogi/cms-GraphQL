<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroserviceAuthentication;

use App\Models\CollaboratorUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

final class LoginCollaborator
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $credentials = [
            'user' => $args['user'],
            'password' => $args['password'],
        ];
        Auth::shouldUse('api_collaborator_user');

        // Verificar si el usuario existe y está activo antes de autenticar
        $user = CollaboratorUser::where('user', $args['user'])->first();

        if (!$user) {
            return $this->formatResponse('NOT OK', 404, 'Usuario no encontrado');
        }

        if ($user->collaborator_status !== 1) {
            return $this->formatResponse('NOT OK', 403, 'El usuario no está activo');
        }

        // Intentar la autenticación
        if (!Auth::attempt($credentials)) {
            return $this->formatResponse('NOT OK', 401, 'Credenciales inválidas');
        }

        // Obtener el ID del usuario autenticado
        $collaboratorUserId = Auth::id();

        // Obtener el usuario autenticado con relaciones
        $collaboratorUser = CollaboratorUser::with('roles', 'permissionCollaborator')->find($collaboratorUserId);

        if (!$collaboratorUser) {
            return $this->formatResponse('NOT OK', 404, 'Usuario no encontrado');
        }

        // Preparar datos adicionales
        $additionalData = [
            'collaborator_user_id' => $collaboratorUserId,
        ];

        // Generar el token con datos adicionales
        $newtoken = Auth::claims($additionalData)->attempt($credentials);

        // Encriptar los datos adicionales
        $jsonData = json_encode($additionalData);
        $encryptedData = Crypt::encryptString($jsonData);

        // Obtener permisos
        $permissions = $collaboratorUser->permissionCollaborator->pluck('permission_id')->toArray();

        return $this->formatResponse('OK', 200, 'Usuario Autenticado', [
            'accessToken' => $newtoken,
            'expiresIn' => 3600,
            'measureTime' => 'seconds',
            'accessType' => 'collaborator',
            'encryptedKey' => $encryptedData,
            'permissions' => $permissions,
        ]);
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
