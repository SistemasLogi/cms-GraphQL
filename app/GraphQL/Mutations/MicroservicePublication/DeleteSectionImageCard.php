<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class DeleteSectionImageCard
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
                $section_id = $args['id'];

                // Buscar la sección
                $section = Section::find($section_id);

                if (!$section) {
                    return $this->formatResponse('NOT OK', 404, 'Sección no encontrada');
                }

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Verificar si existe una imagen de tarjeta para eliminar
                if (!$section->url_card_image) {
                    return $this->formatResponse('NOT OK', 400, 'No hay imagen de tarjeta para eliminar');
                }

                // Eliminar la imagen del almacenamiento
                if (Storage::disk('public')->exists($section->url_card_image)) {
                    Storage::disk('public')->delete($section->url_card_image);
                }

                // Actualizar el campo en la base de datos
                $section->update(['url_card_image' => '']);

                return $this->formatResponse('OK', 200, 'Imagen de tarjeta eliminada exitosamente', ['section' => $section]);
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
            'section' => null,
        ], $data);
    }
}
