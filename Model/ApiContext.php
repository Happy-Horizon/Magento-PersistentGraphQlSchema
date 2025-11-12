<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model;

/**
 * API Context value object for storing request context data
 */
class ApiContext
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = []
    ) {
    }

    /**
     * Get context value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set context value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if context has key
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all context data
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Merge context data (provided context takes precedence)
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function merge(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Clear all context data
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }
}
