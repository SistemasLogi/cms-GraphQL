<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class UpsertEntry
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
                    // Actualizar entrada existente
                    $entry = Entry::find($input['id']);
                    if (!$entry) {
                        return $this->formatResponse('NOT OK', 404, 'Entrada no encontrada');
                    }

                    $entry->update([
                        'section_id' => $input['section_id'] ?? $entry->section_id,
                        'entry_title' => $input['entry_title'] ?? $entry->entry_title,
                        'entry_complement' => $input['entry_complement'] ?? $entry->entry_complement,
                    ]);

                    return $this->formatResponse('OK', 200, 'Entrada actualizada exitosamente', ['entry' => $entry]);
                } else {
                    // Crear nueva entrada
                    $entry = Entry::create([
                        'section_id' => $input['section_id'],
                        'entry_title' => $input['entry_title'] ?? '',
                        'entry_complement' => $input['entry_complement'] ?? '',
                    ]);

                    return $this->formatResponse('OK', 201, 'Entrada creada exitosamente', ['entry' => $entry]);
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
            'entry' => null,
        ], $data);
    }
}
