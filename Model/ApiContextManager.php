<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model;

use HappyHorizon\PersistentGraphQlSchema\Api\ApiContextProviderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * API Context Manager - handles context lifecycle
 */
class ApiContextManager
{
    /**
     * Thread-local storage for context isolation
     *
     * @var array<int, ApiContext>
     */
    private static array $contexts = [];

    /**
     * @param array<ApiContextProviderInterface> $providers
     */
    public function __construct(
        private array $providers = []
    ) {
    }

    /**
     * Initialize context from request
     *
     * @param mixed $request
     * @return ApiContext
     * @throws LocalizedException
     */
    public function initialize(mixed $request): ApiContext
    {
        $context = new ApiContext();
        $threadId = $this->getThreadId();

        // Collect context from all providers
        foreach ($this->providers as $provider) {
            if ($provider instanceof ApiContextProviderInterface) {
                $providerContext = $provider->extract($request);
                if (!empty($providerContext)) {
                    $context->merge($providerContext);
                }
            }
        }

        // Store context with thread isolation
        self::$contexts[$threadId] = $context;

        return $context;
    }

    /**
     * Get current context
     *
     * @return ApiContext|null
     */
    public function getContext(): ?ApiContext
    {
        $threadId = $this->getThreadId();
        return self::$contexts[$threadId] ?? null;
    }

    /**
     * Set context
     *
     * @param ApiContext $context
     * @return void
     */
    public function setContext(ApiContext $context): void
    {
        $threadId = $this->getThreadId();
        self::$contexts[$threadId] = $context;
    }

    /**
     * Clear context for current thread
     *
     * @return void
     */
    public function clear(): void
    {
        $threadId = $this->getThreadId();
        unset(self::$contexts[$threadId]);
    }

    /**
     * Get thread identifier for server-side isolation
     *
     * @return int
     */
    private function getThreadId(): int
    {
        // Use process ID and thread identifier for isolation
        return getmypid();
    }
}
