<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQl;

use HappyHorizon\PersistentGraphQlSchema\Model\ApiContextManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Plugin to initialize API context from GraphQL requests
 */
class GraphQl
{
    /**
     * @param ApiContextManager $contextManager
     * @param RequestInterface $request
     */
    public function __construct(
        private ApiContextManager $contextManager,
        private RequestInterface $request
    ) {
    }

    /**
     * Initialize context before processing GraphQL query
     *
     * @param \Magento\Framework\GraphQl\GraphQl $subject
     * @param string $query
     * @param array $variables
     * @param string|null $operationName
     * @param ContextInterface|null $context
     * @return array
     */
    public function beforeProcess(
        \Magento\Framework\GraphQl\GraphQl $subject,
        string $query,
        array $variables = [],
        ?string $operationName = null,
        ?ContextInterface $context = null
    ): array {
        // Prepare request data for context extraction
        // Include HTTP request, GraphQL context, and variables
        $requestData = [
            'http_request' => $this->request,
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
            'context' => $context
        ];

        // Initialize context from request
        $this->contextManager->initialize($requestData);

        return [$query, $variables, $operationName, $context];
    }

    /**
     * Clear context after processing GraphQL query
     *
     * @param \Magento\Framework\GraphQl\GraphQl $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterProcess(
        \Magento\Framework\GraphQl\GraphQl $subject,
        mixed $result
    ): mixed {
        // Clear context after request processing
        $this->contextManager->clear();
        
        return $result;
    }
}
