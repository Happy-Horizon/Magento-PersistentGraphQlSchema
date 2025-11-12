<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model;

use HappyHorizon\PersistentGraphQlSchema\Api\ApiContextProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Default Context Provider - extracts context from headers and GraphQL variables
 */
class DefaultContextProvider implements ApiContextProviderInterface
{
    /**
     * Extract context from request
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    public function extract(mixed $request): array
    {
        $context = [];

        // Handle array request structure (from GraphQl plugin)
        if (is_array($request)) {
            // Extract HTTP request if available
            if (isset($request['http_request']) && $request['http_request'] instanceof RequestInterface) {
                $httpRequest = $request['http_request'];
                $headers = $httpRequest->getHeaders()->toArray();
                
                // Extract common headers
                $headerKeys = [
                    'authorization',
                    'store',
                    'customer-group-id',
                    'content-type',
                    'accept',
                    'x-forwarded-for',
                    'user-agent'
                ];

                foreach ($headerKeys as $key) {
                    $headerKey = strtolower($key);
                    if (isset($headers[$headerKey])) {
                        $context['headers'][$key] = is_array($headers[$headerKey])
                            ? $headers[$headerKey][0]
                            : $headers[$headerKey];
                    }
                }
            }

            // Extract from GraphQL variables
            if (isset($request['variables']) && is_array($request['variables'])) {
                $context['variables'] = $request['variables'];
            }

            // Extract from GraphQL context
            if (isset($request['context']) && $request['context'] instanceof ContextInterface) {
                $graphQlContext = $request['context'];
                $extensionAttributes = $graphQlContext->getExtensionAttributes();
                
                if ($extensionAttributes) {
                    $store = $extensionAttributes->getStore();
                    if ($store) {
                        $context['store'] = $store->getCode();
                    }
                    $context['customer_group_id'] = $extensionAttributes->getCustomerGroupId();
                }
                
                $context['customer_id'] = $graphQlContext->getUserId();
            }
        }

        // Direct HTTP request extraction
        if ($request instanceof RequestInterface) {
            $headers = $request->getHeaders()->toArray();
            
            $headerKeys = [
                'authorization',
                'store',
                'customer-group-id',
                'content-type',
                'accept',
                'x-forwarded-for',
                'user-agent'
            ];

            foreach ($headerKeys as $key) {
                $headerKey = strtolower($key);
                if (isset($headers[$headerKey])) {
                    $context['headers'][$key] = is_array($headers[$headerKey])
                        ? $headers[$headerKey][0]
                        : $headers[$headerKey];
                }
            }
        }

        // Direct GraphQL context extraction
        if ($request instanceof ContextInterface) {
            $extensionAttributes = $request->getExtensionAttributes();
            
            if ($extensionAttributes) {
                $store = $extensionAttributes->getStore();
                if ($store) {
                    $context['store'] = $store->getCode();
                }
                $context['customer_group_id'] = $extensionAttributes->getCustomerGroupId();
            }
            
            $context['customer_id'] = $request->getUserId();
        }

        return $context;
    }
}
