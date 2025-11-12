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

class ValidateMollieCheckout implements ResolverInterface
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
        $errors = [];
        $isValid = true;

        try {
            $cartId = $args['cart_id'] ?? null;
            $paymentMethod = $args['payment_method'] ?? null;

            if (!$cartId) {
                $errors[] = 'Cart ID is required';
                $isValid = false;
            }

            if (!$paymentMethod) {
                $errors[] = 'Payment method is required';
                $isValid = false;
            }

            if ($isValid) {
                $cart = $this->cartRepository->get($cartId);
                if (!$cart || !$cart->getId()) {
                    $errors[] = 'Cart not found';
                    $isValid = false;
                } else {
                    // Validate cart and payment method
                    $validationErrors = $this->validateCartAndPaymentMethod($cart, $paymentMethod);
                    if (!empty($validationErrors)) {
                        $errors = array_merge($errors, $validationErrors);
                        $isValid = false;
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            $errors[] = 'Cart not found';
            $isValid = false;
            $this->logger->error('Cart validation error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $errors[] = 'An error occurred during validation';
            $isValid = false;
            $this->logger->error('Mollie checkout validation error: ' . $e->getMessage());
        }

        return [
            'is_valid' => $isValid,
            'errors' => $errors
        ];
    }

    /**
     * Validate cart and payment method
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param string $paymentMethod
     * @return array
     */
    protected function validateCartAndPaymentMethod($cart, string $paymentMethod): array
    {
        $errors = [];

        // Validate cart has items
        if ($cart->getItemsCount() === 0) {
            $errors[] = 'Cart is empty';
        }

        // Validate payment method is available
        // TODO: Add actual Mollie payment method validation
        // This should check if the payment method is available for the cart

        return $errors;
    }
}
