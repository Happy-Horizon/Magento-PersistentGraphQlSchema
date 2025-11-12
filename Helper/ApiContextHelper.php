<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Helper;

use HappyHorizon\PersistentGraphQlSchema\Model\ApiContext;
use HappyHorizon\PersistentGraphQlSchema\Model\ApiContextManager;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\HTTP\Client\Curl;

/**
 * API Context Helper for resolvers
 */
class ApiContextHelper extends AbstractHelper
{
    /**
     * @param HelperContext $context
     * @param ApiContextManager $contextManager
     * @param ClientInterface $httpClient
     */
    public function __construct(
        HelperContext $context,
        private ApiContextManager $contextManager,
        private ClientInterface $httpClient
    ) {
        parent::__construct($context);
    }

    /**
     * Fetch API with context
     *
     * @param string $url
     * @param array<string, mixed> $options
     * @return array{body: string, status: int, headers: array<string, string>}
     */
    public function fetchApi(string $url, array $options = []): array
    {
        $apiContext = $this->contextManager->getContext();
        
        // Prepare headers with context
        $headers = [];
        if ($apiContext instanceof ApiContext) {
            $contextHeaders = $apiContext->get('headers', []);
            // Start with context headers
            $headers = $contextHeaders;
        }
        
        // Merge provided headers (provided headers take precedence)
        $providedHeaders = $options['headers'] ?? [];
        $headers = array_merge($headers, $providedHeaders);

        // Add context data to request
        $method = $options['method'] ?? 'GET';
        $body = $options['body'] ?? null;

        // Set headers
        foreach ($headers as $key => $value) {
            $this->httpClient->addHeader($key, $value);
        }

        // Make request
        try {
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                $this->httpClient->post($url, $body);
            } else {
                $this->httpClient->get($url);
            }

            return [
                'body' => $this->httpClient->getBody(),
                'status' => $this->httpClient->getStatus(),
                'headers' => $this->httpClient->getHeaders()
            ];
        } catch (\Exception $e) {
            $this->_logger->error('API fetch failed: ' . $e->getMessage());
            throw $e;
        } finally {
            // Clear headers for next request
            if ($this->httpClient instanceof Curl) {
                $this->httpClient->setHeaders([]);
            }
        }
    }

    /**
     * Get current API context
     *
     * @return ApiContext|null
     */
    public function getContext(): ?ApiContext
    {
        return $this->contextManager->getContext();
    }

    /**
     * Set context value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setContextValue(string $key, mixed $value): void
    {
        $apiContext = $this->contextManager->getContext();
        if ($apiContext instanceof ApiContext) {
            $apiContext->set($key, $value);
        }
    }

    /**
     * Get context value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        $apiContext = $this->contextManager->getContext();
        if ($apiContext instanceof ApiContext) {
            return $apiContext->get($key, $default);
        }
        return $default;
    }
}
