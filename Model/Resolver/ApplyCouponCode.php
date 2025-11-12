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
 * Resolver for shopwareApplyCouponCode mutation
 */
class ApplyCouponCode implements ResolverInterface
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
            $couponCode = $args['couponCode'] ?? null;

            if (!$cartId || !$couponCode) {
                throw new \Exception('Cart ID and coupon code are required');
            }

            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cartData = $this->apiClient->applyCouponCode($cartId, $couponCode, $storeId);

            return $this->formatCartData($cartData);
        } catch (\Exception $e) {
            $this->logger->error('ApplyCouponCode Resolver Error', ['error' => $e->getMessage()]);
            throw new \GraphQL\Error\UserError('Failed to apply coupon code: ' . $e->getMessage());
        }
    }

    /**
     * Format cart data for GraphQL response
     *
     * @param array $cartData
     * @return array
     */
    private function formatCartData(array $cartData): array
    {
        return [
            'id' => $cartData['data']['name'] ?? null,
            'token' => $cartData['data']['token'] ?? null,
            'price' => $cartData['data']['price'] ?? null,
            'lineItems' => $cartData['data']['lineItems'] ?? [],
            'shippingCosts' => $cartData['data']['shippingCosts'] ?? null,
            'totalPrice' => $cartData['data']['price']['totalPrice'] ?? 0,
            'modified' => $cartData['data']['modified'] ?? false,
            'customerComment' => $cartData['data']['customerComment'] ?? null
        ];
    }
}
