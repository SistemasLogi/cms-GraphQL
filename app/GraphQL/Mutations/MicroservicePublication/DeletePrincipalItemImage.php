<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroservicePublication;

use Exception;
use App\Models\CmsItem;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

final class DeletePrincipalItemImage
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
                $cms_item_id = $args['id'];

                $cmsItem = CmsItem::find($cms_item_id);

                if (!$cmsItem) {
                    return $this->formatResponse('NOT OK', 404, 'Ítem no encontrado');
                }

                // Autorización basada en el rol
                if (!in_array($role, ['Administrador', 'Publicador'])) {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Eliminar la imagen si existe
                if ($cmsItem->url_header_image && Storage::disk('public')->exists($cmsItem->url_header_image)) {
                    Storage::disk('public')->delete($cmsItem->url_header_image);
                    $cmsItem->update(['url_header_image' => '']);

                    return $this->formatResponse('OK', 200, 'Imagen eliminada exitosamente', ['cms_item' => $cmsItem]);
                } else {
                    return $this->formatResponse('NOT OK', 404, 'No se encontró la imagen para eliminar');
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
            'cms_item' => null,
        ], $data);
    }
}
