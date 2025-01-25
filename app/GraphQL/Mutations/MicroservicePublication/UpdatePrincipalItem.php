<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\CmsItem;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;



final class UpdatePrincipalItem
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

                // Obtener el ID del ítem
                $cms_item_id = $input['cms_item_id'];
                $cmsItem = CmsItem::find($cms_item_id);

                if (!$cmsItem) {
                    return $this->formatResponse('NOT OK', 404, 'Ítem no encontrado');
                }

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Validar si se proporciona una nueva imagen
                if (!isset($input['url_header_image'])) {
                    return $this->formatResponse('NOT OK', 400, 'Falta parámetro url_header_image');
                }

                $imageFile = $input['url_header_image'];
                $imagePath = $this->storeImage($imageFile);

                if (!$imagePath) {
                    return $this->formatResponse('NOT OK', 400, 'Error al almacenar la imagen');
                }

                // Eliminar la imagen anterior si existe
                if ($cmsItem->url_header_image) {
                    Storage::disk('public')->delete($cmsItem->url_header_image);
                }

                $input['url_header_image'] = $imagePath;

                // Filtrar los campos que pueden actualizarse
                $allowedFields = ['cms_item_title', 'url_header_image', 'text_add'];
                $updateData = array_filter($input, function ($key) use ($allowedFields) {
                    return in_array($key, $allowedFields);
                }, ARRAY_FILTER_USE_KEY);

                // Actualizar el ítem
                $cmsItem->update($updateData);

                return $this->formatResponse('OK', 200, 'Ítem actualizado exitosamente', ['cms_item' => $cmsItem]);
            });
        } catch (Exception $e) {
            return $this->formatResponse('NOT OK', 500, $e->getMessage());
        }
    }

    /**
     * Almacena la imagen en el disco.
     */
    private function storeImage($imageFile)
    {
        $path = $imageFile->store('img/item_principal', 'public');
        return $path;
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
            'cms_item' => null,
        ], $data);
    }
}
