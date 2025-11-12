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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class PlaceMollieOrder implements ResolverInterface
{
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected CartManagementInterface $cartManagement,
        protected OrderRepositoryInterface $orderRepository,
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
            $paymentMethod = $args['payment_method'] ?? null;

            if (!$cartId || !$paymentMethod) {
                throw new LocalizedException(__('Cart ID and payment method are required'));
            }

            $cart = $this->cartRepository->get($cartId);
            if (!$cart || !$cart->getId()) {
                throw new NoSuchEntityException(__('Cart not found'));
            }

            // Set payment method
            $payment = $cart->getPayment();
            $payment->setMethod($paymentMethod);
            $this->cartRepository->save($cart);

            // Place order
            $orderId = $this->cartManagement->placeOrder($cartId);
            $order = $this->orderRepository->get($orderId);

            // Extract redirect URL from payment additional information
            $redirectUrl = $this->extractRedirectUrl($order);

            return [
                'order' => $order,
                'redirect_url' => $redirectUrl
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Order placement error: ' . $e->getMessage());
            throw new \GraphQL\Error\UserError('Cart not found');
        } catch (LocalizedException $e) {
            $this->logger->error('Order placement error: ' . $e->getMessage());
            throw new \GraphQL\Error\UserError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Mollie order placement error: ' . $e->getMessage());
            throw new \GraphQL\Error\UserError('An error occurred while placing the order');
        }
    }

    /**
     * Extract redirect URL from order payment
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string|null
     */
    protected function extractRedirectUrl($order): ?string
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }

        // Extract redirect URL from payment additional information
        $additionalInformation = $payment->getAdditionalInformation();
        
        // Check common Mollie redirect URL keys
        $redirectUrlKeys = [
            'redirect_url',
            'mollie_redirect_url',
            'checkout_url',
            'mollie_checkout_url'
        ];

        foreach ($redirectUrlKeys as $key) {
            if (isset($additionalInformation[$key])) {
                return $additionalInformation[$key];
            }
        }

        return null;
    }
}
