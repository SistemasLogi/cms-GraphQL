<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class DeleteSection
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

                if (!isset($args['id'])) {
                    return $this->formatResponse('NOT OK', 400, 'Falta el parámetro id');
                }

                $section = Section::find($args['id']);
                if (!$section) {
                    return $this->formatResponse('NOT OK', 404, 'Sección no encontrada');
                }

                // Eliminar imágenes asociadas
                $this->deleteImageIfExists($section->url_header_image);
                $this->deleteImageIfExists($section->url_card_image);

                // Eliminar la sección
                $section->delete();

                return $this->formatResponse('OK', 200, 'Sección eliminada exitosamente');
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Elimina una imagen si existe en el almacenamiento.
     */
    private function deleteImageIfExists($imagePath)
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
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
