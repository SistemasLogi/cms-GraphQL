<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroservicePublication;

use Exception;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class EntryList
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

                // Validar roles permitidos
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Obtener el listado de entradas
                $query = Entry::query();
                if (isset($args['id'])) {
                    $query->where('id', $args['id']);
                }

                $entries = $query->with(['section', 'contentEntries', 'fileEntries'])->get();

                return $this->formatResponse('OK', 200, 'Consulta exitosa', ['entries' => $entries]);
            });
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
            'entries' => null,
        ], $data);
    }
}
