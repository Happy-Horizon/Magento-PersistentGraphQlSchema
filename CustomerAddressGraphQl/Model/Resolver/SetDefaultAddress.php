<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HappyHorizon\CustomerAddressGraphQl\Model\Resolver;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Resolver for setDefaultAddress mutation
 */
class SetDefaultAddress implements ResolverInterface
{
    /**
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthenticationException(__('The current customer isn\'t authorized.'));
        }

        $customerId = (int)$context->getUserId();
        $addressId = (int)$args['addressId'];
        $addressType = strtolower($args['addressType']);

        try {
            // Verify the address belongs to the customer
            $address = $this->addressRepository->getById($addressId);
            
            if ((int)$address->getCustomerId() !== $customerId) {
                throw new GraphQlAuthorizationException(
                    __('The address with ID "%1" does not belong to the current customer.', $addressId)
                );
            }

            // Get customer and update default address
            $customer = $this->customerRepository->getById($customerId);

            if ($addressType === 'shipping') {
                $customer->setDefaultShipping($addressId);
            } elseif ($addressType === 'billing') {
                $customer->setDefaultBilling($addressId);
            } else {
                throw new GraphQlInputException(
                    __('Invalid address type. Must be either "shipping" or "billing".')
                );
            }

            $this->customerRepository->save($customer);

            return [
                'success' => true,
                'message' => __('Default %1 address has been updated successfully.', $addressType)
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('The address with ID "%1" does not exist.', $addressId),
                $e
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
    }
}
