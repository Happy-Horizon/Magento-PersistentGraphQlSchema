<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

class MolliePaymentMethods implements ResolverInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        if (!isset($value['model'])) {
            return null;
        }

        try {
            $cart = $value['model'];
            
            // Retrieve Mollie payment methods for the cart
            // This would typically call a Mollie service/API
            $paymentMethods = $this->getMolliePaymentMethods($cart);

            return $paymentMethods;
        } catch (\Exception $e) {
            $this->logger->error('Error resolving Mollie payment methods: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Mollie payment methods for cart
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return array
     */
    protected function getMolliePaymentMethods($cart): array
    {
        // TODO: Implement actual Mollie API call to retrieve payment methods
        // This should integrate with Mollie payment gateway service
        return [];
    }
}
