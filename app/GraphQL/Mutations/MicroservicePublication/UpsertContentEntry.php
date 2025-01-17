<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\ContentEntries;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class UpsertContentEntry
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
                $input = $args['input'];

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                if (isset($input['id'])) {
                    // Actualizar contenido existente
                    $contentEntry = ContentEntries::find($input['id']);

                    if (!$contentEntry) {
                        return $this->formatResponse('NOT OK', 404, 'Contenido no encontrado');
                    }

                    $contentEntry->update([
                        'entry_id' => $input['entry_id'] ?? $contentEntry->entry_id,
                        'content' => $input['content'] ?? $contentEntry->content,
                        'content_type' => $input['content_type'] ?? $contentEntry->content_type,
                        'element_order' => $input['element_order'] ?? $contentEntry->element_order,
                    ]);

                    return $this->formatResponse('OK', 200, 'Contenido actualizado exitosamente', ['contentEntry' => $contentEntry]);
                } else {
                    // Crear nuevo contenido
                    $contentEntry = ContentEntries::create([
                        'entry_id' => $input['entry_id'],
                        'content' => $input['content'],
                        'content_type' => $input['content_type'],
                        'element_order' => $input['element_order'],
                    ]);

                    return $this->formatResponse('OK', 201, 'Contenido creado exitosamente', ['contentEntry' => $contentEntry]);
                }
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
            'contentEntry' => null,
        ], $data);
    }
}
