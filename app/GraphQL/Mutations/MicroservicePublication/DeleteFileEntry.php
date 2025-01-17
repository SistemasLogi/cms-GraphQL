<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\FileEntries;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class DeleteFileEntry
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

                // Buscar el archivo de entrada
                $fileEntry = FileEntries::find($args['id']);

                if (!$fileEntry) {
                    return $this->formatResponse('NOT OK', 404, 'Archivo de entrada no encontrado');
                }

                // Eliminar archivo físico si existe
                $this->deleteFile($fileEntry->url_file);

                // Eliminar el registro de la base de datos
                $fileEntry->delete();

                return $this->formatResponse('OK', 200, 'Archivo de entrada eliminado exitosamente');
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Eliminar archivo físico del almacenamiento.
     */
    private function deleteFile($filePath)
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
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
