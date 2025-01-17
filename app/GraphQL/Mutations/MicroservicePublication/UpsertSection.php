<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class UpsertSection
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

                // Crear o actualizar la sección
                if (isset($input['id'])) {
                    // Actualizar sección existente
                    $section = Section::find($input['id']);
                    if (!$section) {
                        return $this->formatResponse('NOT OK', 404, 'Sección no encontrada');
                    }

                    // Eliminar imágenes antiguas si se van a actualizar
                    $headerImagePath = $this->updateImage($section->url_header_image, $input['url_header_image'] ?? null, 'img/section/header');
                    $cardImagePath = $this->updateImage($section->url_card_image, $input['url_card_image'] ?? null, 'img/section/card');

                    $section->update([
                        'cms_item_id' => $input['cms_item_id'] ?? $section->cms_item_id,
                        'section_title' => $input['section_title'] ?? $section->section_title,
                        'section_description' => $input['section_description'] ?? $section->section_description,
                        'url_header_image' => $headerImagePath ?? $section->url_header_image,
                        'url_card_image' => $cardImagePath ?? $section->url_card_image,
                        'section_type' => $input['section_type'] ?? $section->section_type,
                    ]);

                    return $this->formatResponse('OK', 200, 'Sección actualizada exitosamente', ['section' => $section]);
                } else {
                    // Crear nueva sección
                    $headerImagePath = isset($input['url_header_image']) 
                        ? $this->storeImage($input['url_header_image'], 'img/section/header') 
                        : null;

                    $cardImagePath = isset($input['url_card_image']) 
                        ? $this->storeImage($input['url_card_image'], 'img/section/card') 
                        : null;

                    $section = Section::create([
                        'cms_item_id' => $input['cms_item_id'],
                        'section_title' => $input['section_title'] ?? '',
                        'section_description' => $input['section_description'] ?? '',
                        'url_header_image' => $headerImagePath ?? '',
                        'url_card_image' => $cardImagePath ?? '',
                        'section_type' => $input['section_type'],
                    ]);

                    return $this->formatResponse('OK', 201, 'Sección creada exitosamente', ['section' => $section]);
                }
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Almacena una imagen en la ruta especificada.
     */
    private function storeImage($imageFile, $path)
    {
        try {
            return $imageFile->store($path, 'public');
        } catch (Exception $e) {
            throw new Exception("Error al almacenar la imagen en {$path}: " . $e->getMessage());
        }
    }

    /**
     * Actualiza una imagen, eliminando la existente si es necesario.
     */
    private function updateImage($currentImagePath, $newImageFile, $path)
    {
        if ($newImageFile) {
            // Eliminar la imagen anterior si existe
            if ($currentImagePath && Storage::disk('public')->exists($currentImagePath)) {
                Storage::disk('public')->delete($currentImagePath);
            }

            // Almacenar la nueva imagen
            return $this->storeImage($newImageFile, $path);
        }

        // Si no hay nueva imagen, mantener la actual
        return $currentImagePath;
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
