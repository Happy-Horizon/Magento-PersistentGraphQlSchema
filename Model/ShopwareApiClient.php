<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HappyHorizon\ShopwareCheckoutGraphQl\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Shopware Store API Client
 */
class ShopwareApiClient
{
    const XML_PATH_SHOPWARE_API_URL = 'shopware/api/url';
    const XML_PATH_SHOPWARE_API_ACCESS_KEY = 'shopware/api/access_key';
    const XML_PATH_SHOPWARE_API_SECRET_KEY = 'shopware/api/secret_key';
    const XML_PATH_SHOPWARE_API_SALES_CHANNEL_ID = 'shopware/api/sales_channel_id';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Get Shopware API base URL
     *
     * @param int|null $storeId
     * @return string
     */
    private function getApiUrl($storeId = null): string
    {
        return rtrim($this->scopeConfig->getValue(
            self::XML_PATH_SHOPWARE_API_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ), '/');
    }

    /**
     * Get Shopware API access key
     *
     * @param int|null $storeId
     * @return string
     */
    private function getAccessKey($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SHOPWARE_API_ACCESS_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Shopware API secret key
     *
     * @param int|null $storeId
     * @return string
     */
    private function getSecretKey($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SHOPWARE_API_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Shopware sales channel ID
     *
     * @param int|null $storeId
     * @return string
     */
    private function getSalesChannelId($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SHOPWARE_API_SALES_CHANNEL_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Make API request to Shopware Store API
     *
     * @param string $endpoint
     * @param string $method
     * @param array|null $data
     * @param int|null $storeId
     * @return array
     * @throws \Exception
     */
    public function request(string $endpoint, string $method = 'GET', ?array $data = null, $storeId = null): array
    {
        $apiUrl = $this->getApiUrl($storeId);
        $accessKey = $this->getAccessKey($storeId);
        $secretKey = $this->getSecretKey($storeId);

        if (empty($apiUrl) || empty($accessKey) || empty($secretKey)) {
            throw new \Exception('Shopware API credentials are not configured');
        }

        $url = $apiUrl . '/store-api' . $endpoint;
        
        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'sw-access-key' => $accessKey,
            'sw-context-token' => $this->getContextToken($storeId)
        ]);

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_TIMEOUT, 30);

        try {
            switch (strtoupper($method)) {
                case 'POST':
                    $this->curl->post($url, $data ? $this->json->serialize($data) : '');
                    break;
                case 'PUT':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                    $this->curl->post($url, $data ? $this->json->serialize($data) : '');
                    break;
                case 'PATCH':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
                    $this->curl->post($url, $data ? $this->json->serialize($data) : '');
                    break;
                case 'DELETE':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                    $this->curl->get($url);
                    break;
                default:
                    $this->curl->get($url);
                    break;
            }

            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            if ($statusCode >= 400) {
                $this->logger->error('Shopware API Error', [
                    'url' => $url,
                    'status' => $statusCode,
                    'response' => $response
                ]);
                throw new \Exception('Shopware API request failed: ' . $response);
            }

            return $this->json->unserialize($response);
        } catch (\Exception $e) {
            $this->logger->error('Shopware API Exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get context token (simplified - in production, this should be managed per session)
     *
     * @param int|null $storeId
     * @return string
     */
    private function getContextToken($storeId = null): string
    {
        // In a real implementation, this should be stored per session/cart
        // For now, we'll generate a new context token or use a stored one
        return bin2hex(random_bytes(16));
    }

    /**
     * Get cart from Shopware
     *
     * @param string $cartId
     * @param int|null $storeId
     * @return array
     */
    public function getCart(string $cartId, $storeId = null): array
    {
        return $this->request('/checkout/cart?name=' . $cartId, 'GET', null, $storeId);
    }

    /**
     * Set address info on cart
     *
     * @param string $cartId
     * @param string $addressType
     * @param array $addressData
     * @param int|null $storeId
     * @return array
     */
    public function setAddressInfo(string $cartId, string $addressType, array $addressData, $storeId = null): array
    {
        $endpoint = '/checkout/cart/address';
        $data = array_merge([
            'addressId' => null,
            'type' => $addressType
        ], $addressData);

        return $this->request($endpoint, 'POST', $data, $storeId);
    }

    /**
     * Place order
     *
     * @param string $cartId
     * @param int|null $storeId
     * @return array
     */
    public function placeOrder(string $cartId, $storeId = null): array
    {
        return $this->request('/checkout/order', 'POST', [], $storeId);
    }

    /**
     * Apply coupon code
     *
     * @param string $cartId
     * @param string $couponCode
     * @param int|null $storeId
     * @return array
     */
    public function applyCouponCode(string $cartId, string $couponCode, $storeId = null): array
    {
        $endpoint = '/checkout/cart/line-item';
        $data = [
            'items' => [
                [
                    'type' => 'promotion',
                    'referencedId' => $couponCode
                ]
            ]
        ];

        return $this->request($endpoint, 'POST', $data, $storeId);
    }

    /**
     * Get user information
     *
     * @param int|null $storeId
     * @return array
     */
    public function getUser($storeId = null): array
    {
        return $this->request('/account/customer', 'GET', null, $storeId);
    }
}
