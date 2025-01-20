<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MicroservicePublication;

use Exception;
use App\Models\CmsItem;

final class CmsItemList
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        try {

            // Filtrar por id si está presente
            $query = CmsItem::query();

            if (!empty($args['id'])) {
                $query->where('id', $args['id']);
            }

            $cmsItems = $query->with('sections')->get();

            return $this->formatResponse('OK', 200, 'Listado obtenido exitosamente', ['cms_items' => $cmsItems]);
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
            'cms_items' => null,
        ], $data);
    }
}
