<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HappyHorizon\HreflangGraphQl\Model;

use Magento\Framework\App\Area;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Generator for hreflang URLs
 * Handles absolute alternate URLs generation, locale normalization, and store emulation
 */
class HreflangUrlGenerator
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation $emulation,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate hreflang tag value from locale
     *
     * @param string $locale
     * @return string
     */
    public function normalizeLocale(string $locale): string
    {
        // Normalize locale format: "en_US" -> "en", "nl_NL" -> "nl"
        $parts = explode('_', $locale);
        return strtolower($parts[0] ?? $locale);
    }

    /**
     * Get alternate URLs for a product across all available stores
     *
     * @param int $productId
     * @param int|null $currentStoreId
     * @return array Array of ['hreflang' => 'locale', 'url' => 'absolute_url']
     */
    public function getProductAlternateUrls(int $productId, ?int $currentStoreId = null): array
    {
        $alternateUrls = [];
        $websiteId = null;

        try {
            if ($currentStoreId) {
                $currentStore = $this->storeManager->getStore($currentStoreId);
                $websiteId = $currentStore->getWebsiteId();
            }

            $stores = $this->getStoresForWebsite($websiteId);

            foreach ($stores as $store) {
                try {
                    // Check if product exists in this store
                    if (!$this->isProductAvailableInStore($productId, $store->getId())) {
                        continue;
                    }

                    $url = $this->getProductUrl($productId, $store);
                    $locale = $this->normalizeLocale($store->getLocaleCode());

                    $alternateUrls[] = [
                        'hreflang' => $locale,
                        'url' => $url
                    ];
                } catch (\Exception $e) {
                    $this->logger->warning(
                        sprintf(
                            'Failed to generate hreflang URL for product %d in store %d: %s',
                            $productId,
                            $store->getId(),
                            $e->getMessage()
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error generating product alternate URLs: %s', $e->getMessage())
            );
        }

        return $alternateUrls;
    }

    /**
     * Get alternate URLs for a category across all available stores
     *
     * @param int $categoryId
     * @param int|null $currentStoreId
     * @return array Array of ['hreflang' => 'locale', 'url' => 'absolute_url']
     */
    public function getCategoryAlternateUrls(int $categoryId, ?int $currentStoreId = null): array
    {
        $alternateUrls = [];
        $websiteId = null;

        try {
            if ($currentStoreId) {
                $currentStore = $this->storeManager->getStore($currentStoreId);
                $websiteId = $currentStore->getWebsiteId();
            }

            $stores = $this->getStoresForWebsite($websiteId);

            foreach ($stores as $store) {
                try {
                    // Check if category exists in this store
                    if (!$this->isCategoryAvailableInStore($categoryId, $store->getId())) {
                        continue;
                    }

                    $url = $this->getCategoryUrl($categoryId, $store);
                    $locale = $this->normalizeLocale($store->getLocaleCode());

                    $alternateUrls[] = [
                        'hreflang' => $locale,
                        'url' => $url
                    ];
                } catch (\Exception $e) {
                    $this->logger->warning(
                        sprintf(
                            'Failed to generate hreflang URL for category %d in store %d: %s',
                            $categoryId,
                            $store->getId(),
                            $e->getMessage()
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error generating category alternate URLs: %s', $e->getMessage())
            );
        }

        return $alternateUrls;
    }

    /**
     * Get stores filtered by website
     *
     * @param int|null $websiteId
     * @return StoreInterface[]
     */
    private function getStoresForWebsite(?int $websiteId = null): array
    {
        $stores = [];

        try {
            if ($websiteId) {
                $website = $this->storeManager->getWebsite($websiteId);
                $storeIds = $website->getStoreIds();
                foreach ($storeIds as $storeId) {
                    $store = $this->storeManager->getStore($storeId);
                    if ($store->isActive()) {
                        $stores[] = $store;
                    }
                }
            } else {
                // Get all active stores
                foreach ($this->storeManager->getStores() as $store) {
                    if ($store->isActive()) {
                        $stores[] = $store;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error getting stores for website %s: %s', $websiteId ?? 'all', $e->getMessage())
            );
        }

        return $stores;
    }

    /**
     * Check if product is available in store
     *
     * @param int $productId
     * @param int $storeId
     * @return bool
     */
    private function isProductAvailableInStore(int $productId, int $storeId): bool
    {
        try {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $product = $this->productRepository->getById($productId, false, $storeId);
            $this->emulation->stopEnvironmentEmulation();

            return $product->isAvailable() && $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
        } catch (\Exception $e) {
            $this->emulation->stopEnvironmentEmulation();
            return false;
        }
    }

    /**
     * Check if category is available in store
     *
     * @param int $categoryId
     * @param int $storeId
     * @return bool
     */
    private function isCategoryAvailableInStore(int $categoryId, int $storeId): bool
    {
        try {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $category = $this->categoryRepository->get($categoryId, $storeId);
            $this->emulation->stopEnvironmentEmulation();

            return $category->getIsActive();
        } catch (\Exception $e) {
            $this->emulation->stopEnvironmentEmulation();
            return false;
        }
    }

    /**
     * Get absolute product URL for a specific store
     *
     * @param int $productId
     * @param StoreInterface $store
     * @return string
     */
    private function getProductUrl(int $productId, StoreInterface $store): string
    {
        try {
            $this->emulation->startEnvironmentEmulation($store->getId(), Area::AREA_FRONTEND, true);
            $product = $this->productRepository->getById($productId, false, $store->getId());
            $url = $product->getUrlModel()->getUrl($product, ['_scope' => $store->getId()]);
            $this->emulation->stopEnvironmentEmulation();

            // Ensure absolute URL
            if (!preg_match('/^https?:\/\//', $url)) {
                $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
                $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
            }

            return $url;
        } catch (\Exception $e) {
            $this->emulation->stopEnvironmentEmulation();
            throw $e;
        }
    }

    /**
     * Get absolute category URL for a specific store
     *
     * @param int $categoryId
     * @param StoreInterface $store
     * @return string
     */
    private function getCategoryUrl(int $categoryId, StoreInterface $store): string
    {
        try {
            $this->emulation->startEnvironmentEmulation($store->getId(), Area::AREA_FRONTEND, true);
            $category = $this->categoryRepository->get($categoryId, $store->getId());
            $url = $category->getUrl();
            $this->emulation->stopEnvironmentEmulation();

            // Ensure absolute URL
            if (!preg_match('/^https?:\/\//', $url)) {
                $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
                $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
            }

            return $url;
        } catch (\Exception $e) {
            $this->emulation->stopEnvironmentEmulation();
            throw $e;
        }
    }

    /**
     * Format alternate URLs as comma-separated string
     *
     * @param array $alternateUrls
     * @return string
     */
    public function formatAlternateUrls(array $alternateUrls): string
    {
        $formatted = [];
        foreach ($alternateUrls as $alternate) {
            if (isset($alternate['url'])) {
                $formatted[] = $alternate['url'];
            }
        }
        return implode(',', $formatted);
    }

    /**
     * Get current hreflang value for a store
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCurrentHreflang(?int $storeId = null): string
    {
        try {
            if ($storeId) {
                $store = $this->storeManager->getStore($storeId);
            } else {
                $store = $this->storeManager->getStore();
            }
            return $this->normalizeLocale($store->getLocaleCode());
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error getting current hreflang: %s', $e->getMessage())
            );
            return '';
        }
    }
}
