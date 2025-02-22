<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\FileEntries;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class UpsertFileEntry
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

                // Crear o actualizar un archivo de entrada
                if (isset($input['id'])) {
                    // Actualizar archivo de entrada existente
                    $fileEntry = FileEntries::find($input['id']);
                    if (!$fileEntry) {
                        return $this->formatResponse('NOT OK', 404, 'Archivo de entrada no encontrado');
                    }

                    // Eliminar archivo antiguo si se reemplaza
                    $filePath = $this->updateFile($fileEntry->url_file, $input['url_file'] ?? null, 'files/entries');

                    // Actualizar los datos del archivo de entrada
                    $fileEntry->update([
                        'entry_id' => $input['entry_id'] ?? $fileEntry->entry_id,
                        'url_file' => $filePath ?? $fileEntry->url_file,
                        'file_type' => $input['file_type'] ?? $fileEntry->file_type,
                        'element_order' => $input['element_order'] ?? $fileEntry->element_order,
                        'orientation_img' => $input['orientation_img'] ?? $fileEntry->orientation_img,
                    ]);

                    return $this->formatResponse('OK', 200, 'Archivo de entrada actualizado exitosamente', ['file_entry' => $fileEntry]);
                } else {
                    // Crear nuevo archivo de entrada
                    $filePath = isset($input['url_file'])
                        ? $this->storeFile($input['url_file'], 'files/entries')
                        : null;

                    $fileEntry = FileEntries::create([
                        'entry_id' => $input['entry_id'],
                        'url_file' => $filePath ?? '',
                        'file_type' => $input['file_type'],
                        'element_order' => $input['element_order'],
                        'orientation_img' => $input['orientation_img'] ?? null,
                    ]);

                    return $this->formatResponse('OK', 201, 'Archivo de entrada creado exitosamente', ['file_entry' => $fileEntry]);
                }
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Almacena un archivo en la ruta especificada.
     */
    private function storeFile($file, $path)
    {
        try {
            return $file->store($path, 'public');
        } catch (Exception $e) {
            throw new Exception("Error al almacenar el archivo en {$path}: " . $e->getMessage());
        }
    }

    /**
     * Actualiza un archivo, eliminando el antiguo si es necesario.
     */
    private function updateFile($currentFilePath, $newFile, $path)
    {
        if ($newFile) {
            // Eliminar el archivo anterior si existe
            if ($currentFilePath && Storage::disk('public')->exists($currentFilePath)) {
                Storage::disk('public')->delete($currentFilePath);
            }

            // Almacenar el nuevo archivo
            return $this->storeFile($newFile, $path);
        }

        // Si no hay nuevo archivo, mantener el actual
        return $currentFilePath;
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
            'file_entry' => null,
        ], $data);
    }
}
