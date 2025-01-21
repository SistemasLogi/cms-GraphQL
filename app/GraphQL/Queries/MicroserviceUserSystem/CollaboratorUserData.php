<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroserviceUserSystem;

use Exception;
use App\Models\CollaboratorUser;
use Tymon\JWTAuth\Facades\JWTAuth;

final class CollaboratorUserData
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {
            // Obtener el token y la información del payload
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $role = $payload->get('role');
            $sub = $payload->get('sub'); // ID del colaborador desde el token

            switch ($role) {
                case 'Publicador':
                    // Validar estado del colaborador
                    $collaborator = CollaboratorUser::find($sub);

                    if (!$collaborator || $collaborator->collaborator_status !== 1) {
                        return $this->formatResponse('NOT OK', 403, 'Acceso denegado. Usuario inactivo o no encontrado.');
                    }

                    // Devolver la información del colaborador como una lista
                    return $this->formatResponse('OK', 200, 'Usuario obtenido exitosamente', [
                        'collaborator_user' => [$collaborator],
                    ]);

                case 'Administrador':
                    if (!empty($args['collaborator_user_id'])) {
                        $collaborator = CollaboratorUser::find($args['collaborator_user_id']);

                        if (!$collaborator) {
                            return $this->formatResponse('NOT OK', 404, 'Colaborador no encontrado.');
                        }

                        // Devolver el colaborador como una lista
                        return $this->formatResponse('OK', 200, 'Usuario obtenido exitosamente', [
                            'collaborator_user' => [$collaborator],
                        ]);
                    }

                    // Si no se proporciona `collaborator_user_id`, devolver todos los usuarios
                    $collaborators = CollaboratorUser::all();

                    return $this->formatResponse('OK', 200, 'Listado de usuarios obtenido exitosamente', [
                        'collaborator_user' => $collaborators,
                    ]);

                default:
                    // Rol no permitido
                    return $this->formatResponse('NOT OK', 403, 'Acceso denegado.');
            }
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
            'collaborator_user' => [],
        ], $data);
    }
}
