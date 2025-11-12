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

class MollieRedirectUrl implements ResolverInterface
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
     * @return string|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?string {
        if (!isset($value['model'])) {
            return null;
        }

        try {
            $cart = $value['model'];
            
            // Extract redirect URL from cart payment
            $redirectUrl = $this->extractRedirectUrl($cart);

            return $redirectUrl;
        } catch (\Exception $e) {
            $this->logger->error('Error resolving Mollie redirect URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract redirect URL from cart payment
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return string|null
     */
    protected function extractRedirectUrl($cart): ?string
    {
        $payment = $cart->getPayment();
        if (!$payment) {
            return null;
        }

        // Extract redirect URL from payment additional information
        $additionalInformation = $payment->getAdditionalInformation();
        
        if (!$additionalInformation || !is_array($additionalInformation)) {
            return null;
        }

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
