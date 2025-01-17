<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class DeleteEntry
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

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                $entryId = $args['id'] ?? null;
                if (!$entryId) {
                    return $this->formatResponse('NOT OK', 400, 'ID de la entrada es obligatorio');
                }

                $entry = Entry::find($entryId);
                if (!$entry) {
                    return $this->formatResponse('NOT OK', 404, 'Entrada no encontrada');
                }

                // Eliminar relaciones asociadas (si las hay)
                $entry->contentEntries()->delete();
                $entry->fileEntries()->delete();

                // Eliminar la entrada
                $entry->delete();

                return $this->formatResponse('OK', 200, 'Entrada eliminada exitosamente');
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
