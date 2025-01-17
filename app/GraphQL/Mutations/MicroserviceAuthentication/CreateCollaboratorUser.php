<?php declare(strict_types=1);

namespace App\GraphQL\Mutations\MicroserviceAuthentication;

use Exception;
use App\Models\CollaboratorUser;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

final class CreateCollaboratorUser
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

                // Crear el usuario con contraseña encriptada
                $user = CollaboratorUser::create([
                    'roles_id' => $input['roles_id'],
                    'document_number' => $input['document_number'],
                    'collaborator_name' => $input['collaborator_name'],
                    'collaborator_email' => $input['collaborator_email'],
                    'collaborator_status' => $input['collaborator_status'] ?? 1,
                    'user' => $input['user'],
                    'password' => Hash::make($input['password']),
                ]);

                return $this->formatResponse('OK', 201, 'Usuario creado exitosamente', ['collaborator_user' => $user]);
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
