<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HappyHorizon\ShopwareCheckoutGraphQl\Model\Resolver;

use HappyHorizon\ShopwareCheckoutGraphQl\Model\ShopwareApiClient;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolver for shopwareUser query
 */
class GetUser implements ResolverInterface
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
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $userData = $this->apiClient->getUser($storeId);

            return $this->formatUserData($userData);
        } catch (\Exception $e) {
            $this->logger->error('GetUser Resolver Error', ['error' => $e->getMessage()]);
            throw new \GraphQL\Error\UserError('Failed to retrieve user: ' . $e->getMessage());
        }
    }

    /**
     * Format user data for GraphQL response
     *
     * @param array $userData
     * @return array
     */
    private function formatUserData(array $userData): array
    {
        return [
            'id' => $userData['data']['id'] ?? null,
            'customerId' => $userData['data']['customerId'] ?? null,
            'firstName' => $userData['data']['firstName'] ?? null,
            'lastName' => $userData['data']['lastName'] ?? null,
            'email' => $userData['data']['email'] ?? null,
            'active' => $userData['data']['active'] ?? false,
            'guest' => $userData['data']['guest'] ?? false,
            'firstLogin' => $userData['data']['firstLogin'] ?? null,
            'lastLogin' => $userData['data']['lastLogin'] ?? null,
            'salesChannelId' => $userData['data']['salesChannelId'] ?? null,
            'languageId' => $userData['data']['languageId'] ?? null,
            'addresses' => $userData['data']['addresses'] ?? []
        ];
    }
}
