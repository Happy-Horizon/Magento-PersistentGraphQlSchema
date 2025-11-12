<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Api;

/**
 * API Context Provider Interface
 */
interface ApiContextProviderInterface
{
    /**
     * Extract context from request
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    public function extract(mixed $request): array;
}
