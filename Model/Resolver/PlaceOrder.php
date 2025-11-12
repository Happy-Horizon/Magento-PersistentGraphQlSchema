<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HappyHorizon\ShopwareCheckoutGraphQl\Model\Resolver;

use HappyHorizon\ShopwareCheckoutGraphQl\Model\ShopwareApiClient;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Mutation\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolver for shopwarePlaceOrder mutation
 */
class PlaceOrder implements ResolverInterface
{
    /**
     * @var ShopwareApiClient
     */
    private $apiClient;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShopwareApiClient $apiClient
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShopwareApiClient $apiClient,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        try {
            $cartId = $args['cartId'] ?? null;
            if (!$cartId) {
                throw new \Exception('Cart ID is required');
            }

            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $orderData = $this->apiClient->placeOrder($cartId, $storeId);

            return $this->formatOrderData($orderData);
        } catch (\Exception $e) {
            $this->logger->error('PlaceOrder Resolver Error', ['error' => $e->getMessage()]);
            throw new \GraphQL\Error\UserError('Failed to place order: ' . $e->getMessage());
        }
    }

    /**
     * Format order data for GraphQL response
     *
     * @param array $orderData
     * @return array
     */
    private function formatOrderData(array $orderData): array
    {
        return [
            'id' => $orderData['data']['id'] ?? null,
            'orderNumber' => $orderData['data']['orderNumber'] ?? null,
            'orderDate' => $orderData['data']['orderDateTime'] ?? null,
            'price' => $orderData['data']['price'] ?? null,
            'stateId' => $orderData['data']['stateId'] ?? null,
            'stateMachineState' => $orderData['data']['stateMachineState'] ?? null,
            'transactions' => $orderData['data']['transactions'] ?? [],
            'lineItems' => $orderData['data']['lineItems'] ?? [],
            'addresses' => $orderData['data']['addresses'] ?? []
        ];
    }
}
