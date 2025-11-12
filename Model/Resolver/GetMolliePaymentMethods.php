<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class GetMolliePaymentMethods implements ResolverInterface
{
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        try {
            $cartId = $args['cart_id'] ?? null;
            if (!$cartId) {
                return ['payment_methods' => []];
            }

            $cart = $this->cartRepository->get($cartId);
            if (!$cart || !$cart->getId()) {
                return ['payment_methods' => []];
            }

            // Retrieve Mollie payment methods
            // This would typically call a Mollie service/API
            $paymentMethods = $this->getMolliePaymentMethods($cart);

            return [
                'payment_methods' => $paymentMethods
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Cart not found: ' . $e->getMessage());
            return ['payment_methods' => []];
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mollie payment methods: ' . $e->getMessage());
            return ['payment_methods' => []];
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
