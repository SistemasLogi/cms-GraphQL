<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroserviceAuthentication;

use Exception;
use App\Models\CollaboratorUser;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

final class UpdateCollaboratorUser
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

                // Validar rol de administrador
                if ($role !== 'Administrador') {
                    return $this->formatResponse('NOT OK', 403, 'Acceso no autorizado');
                }

                // Buscar el usuario por ID
                $user = CollaboratorUser::find($input['id']);
                if (!$user) {
                    return $this->formatResponse('NOT OK', 404, 'Usuario no encontrado');
                }

                // Actualizar los datos enviados
                $updateData = array_filter([
                    'roles_id' => $input['roles_id'] ?? null,
                    'document_number' => $input['document_number'] ?? null,
                    'collaborator_name' => $input['collaborator_name'] ?? null,
                    'collaborator_email' => $input['collaborator_email'] ?? null,
                    'collaborator_status' => $input['collaborator_status'] ?? null,
                    'user' => $input['user'] ?? null,
                    'password' => isset($input['password']) ? Hash::make($input['password']) : null,
                ], function ($value) {
                    return $value !== null; // 🔹 Esto permite conservar el 0
                });

                $user->update($updateData);

                return $this->formatResponse('OK', 200, 'Usuario actualizado exitosamente', ['collaborator_user' => $user]);
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
            'collaborator_user' => null,
        ], $data);
    }
}
