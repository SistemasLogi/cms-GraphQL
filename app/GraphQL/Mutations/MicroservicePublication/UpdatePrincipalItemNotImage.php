<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\CmsItem;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class UpdatePrincipalItemNotImage
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

                // Filtrar los campos permitidos (excluyendo la imagen)
                $allowedFields = ['cms_item_title', 'text_add'];
                $updateData = array_filter($input, function ($key) use ($allowedFields) {
                    return in_array($key, $allowedFields);
                }, ARRAY_FILTER_USE_KEY);

                if (empty($updateData)) {
                    return $this->formatResponse('NOT OK', 400, 'No hay campos válidos para actualizar');
                }

                // Actualizar el ítem
                $cmsItem->update($updateData);

                return $this->formatResponse('OK', 200, 'Ítem actualizado exitosamente', ['cms_item' => $cmsItem]);
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
            'cms_item' => null,
        ], $data);
    }
}
