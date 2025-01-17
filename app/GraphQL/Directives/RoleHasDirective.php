<?php declare(strict_types=1);

namespace App\GraphQL\Directives;

use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

final class RoleHasDirective extends BaseDirective implements FieldMiddleware
{
    // TODO implement the directive https://lighthouse-php.com/master/custom-directives/getting-started.html

    public static function definition(): string
    {
        return /** @lang GraphQL */<<<'GRAPHQL'
        directive @roleHas(roles: [String!]!) on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    /**
     * @param  mixed  $root  The result of the parent resolver.
     * @param  mixed|\Nuwave\Lighthouse\Execution\Arguments\ArgumentSet|array<\Nuwave\Lighthouse\Execution\Arguments\ArgumentSet>  $value  The slice of arguments that belongs to this nested resolver.
     *
     * @return mixed|void|null May return the modified $root
     */
    public function handleField(FieldValue $fieldValue): void
    {
        $allowedRoles = $this->directiveArgValue('roles'); // Obtener los roles permitidos

        $fieldValue->wrapResolver(fn(callable $resolver): \Closure => function ($root, $args, $context, ResolveInfo $resolveInfo) use ($resolver, $allowedRoles) {
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $role = $payload->get('role');

            if (in_array($role, $allowedRoles)) { // Verificar si el rol actual está en los roles permitidos
                return $resolver($root, $args, $context, $resolveInfo);
            } else {
                throw new Exception('Acceso no autorizado');
            }
        });

    }
}
