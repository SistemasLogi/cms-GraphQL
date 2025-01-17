<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\ContentEntries;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class DeleteContentEntry
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
                $id = $args['id'];

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Buscar el contenido
                $contentEntry = ContentEntries::find($id);

                if (!$contentEntry) {
                    return $this->formatResponse('NOT OK', 404, 'Contenido no encontrado');
                }

                // Eliminar el contenido
                $contentEntry->delete();

                return $this->formatResponse('OK', 200, 'Contenido eliminado exitosamente');
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
        ], $data);
    }
}
