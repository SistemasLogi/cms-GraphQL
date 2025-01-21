<?php declare(strict_types=1);

namespace App\GraphQL\Queries\MicroserviceUserSystem;

use Exception;
use App\Models\CollaboratorUser;
use Tymon\JWTAuth\Facades\JWTAuth;

final class CollaboratorUserByToken
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {
            // Obtener el token y el `sub` del payload
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $sub = $payload->get('sub'); // ID del colaborador desde el token

            // Buscar el colaborador en la base de datos
            $collaborator = CollaboratorUser::find($sub);

            if (!$collaborator || $collaborator->collaborator_status !== 1) {
                return $this->formatResponse('NOT OK', 404, 'Colaborador no encontrado o inactivo.');
            }

            // Devolver la información del colaborador
            return $this->formatResponse('OK', 200, 'Usuario obtenido exitosamente', [
                'collaborator_user' => $collaborator,
            ]);
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
            'collaborator_user' => null,
        ], $data);
    }
}
