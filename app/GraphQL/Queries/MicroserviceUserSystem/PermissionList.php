<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroserviceUserSystem;

use Exception;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class PermissionList
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {
            return DB::transaction(function () {
                // Obtener y validar el token
                $token = JWTAuth::parseToken();
                $payload = $token->getPayload();
                $role = $payload->get('role');

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Obtener el listado de permisos
                $permissions = Permission::all();

                return $this->formatResponse('OK', 200, 'Listado de permisos obtenido exitosamente', ['permissions' => $permissions]);
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Estructura de respuesta estándar.
     */
    private function formatResponse($status, $statusCode, $statusMessage, $data = [])
    {
        return array_merge([
            'status' => $status,
            'status_code' => $statusCode,
            'status_message' => $statusMessage,
            'permissions' => null,
        ], $data);
    }
}
