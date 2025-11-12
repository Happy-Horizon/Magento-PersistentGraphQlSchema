<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\App\Emulation;

class AlternateUrlHelper extends AbstractHelper
{
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param PageRepositoryInterface $pageRepository
     * @param Emulation $emulation
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        protected ProductRepositoryInterface $productRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected PageRepositoryInterface $pageRepository,
        protected Emulation $emulation
    ) {
        parent::__construct($context);
    }

    /**
     * Generate alternate URLs for a product across all stores
     *
     * @param int $productId
     * @param int|null $currentStoreId
     * @return array
     */
    public function getProductAlternateUrls(int $productId, ?int $currentStoreId = null): array
    {
        $alternateUrls = [];
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            if ($currentStoreId && (int)$store->getId() === $currentStoreId) {
                continue; // Skip current store
            }

            try {
                // Emulate store context for proper URL generation
                $this->emulation->startEnvironmentEmulation($store->getId());
                
                try {
                    $product = $this->productRepository->getById($productId, false, $store->getId());
                    
                    if ($product->getId() && $product->isVisibleInSiteVisibility()) {
                        $url = $product->getProductUrl();
                        $hreflang = $this->convertLocaleToHreflang($store->getCode());
                        
                        $alternateUrls[] = [
                            'hreflang' => $hreflang,
                            'url' => $url
                        ];
                    }
                } finally {
                    $this->emulation->stopEnvironmentEmulation();
                }
            } catch (NoSuchEntityException $e) {
                // Product not available in this store, skip
                continue;
            } catch (\Exception $e) {
                // Skip on any error
                continue;
            }
        }

        return $alternateUrls;
    }

    /**
     * Generate alternate URLs for a category across all stores
     *
     * @param int $categoryId
     * @param int|null $currentStoreId
     * @return array
     */
    public function getCategoryAlternateUrls(int $categoryId, ?int $currentStoreId = null): array
    {
        $alternateUrls = [];
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            if ($currentStoreId && (int)$store->getId() === $currentStoreId) {
                continue; // Skip current store
            }

            try {
                // Emulate store context for proper URL generation
                $this->emulation->startEnvironmentEmulation($store->getId());
                
                try {
                    $category = $this->categoryRepository->get($categoryId, $store->getId());
                    
                    if ($category->getId() && $category->getIsActive()) {
                        $url = $category->getUrl();
                        $hreflang = $this->convertLocaleToHreflang($store->getCode());
                        
                        $alternateUrls[] = [
                            'hreflang' => $hreflang,
                            'url' => $url
                        ];
                    }
                } finally {
                    $this->emulation->stopEnvironmentEmulation();
                }
            } catch (NoSuchEntityException $e) {
                // Category not available in this store, skip
                continue;
            } catch (\Exception $e) {
                // Skip on any error
                continue;
            }
        }

        return $alternateUrls;
    }

    /**
     * Generate alternate URLs for a CMS page across all stores
     *
     * @param int $pageId
     * @param int|null $currentStoreId
     * @return array
     */
    public function getCmsPageAlternateUrls(int $pageId, ?int $currentStoreId = null): array
    {
        $alternateUrls = [];
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            if ($currentStoreId && (int)$store->getId() === $currentStoreId) {
                continue; // Skip current store
            }

            try {
                $page = $this->pageRepository->getById($pageId);
                
                // Check if page is available in this store
                $storeIds = $page->getStoreId();
                if ($storeIds == Store::DEFAULT_STORE_ID || (is_array($storeIds) && in_array($store->getId(), $storeIds))) {
                    $storeBaseUrl = $this->storeManager->getStore($store->getId())
                        ->getBaseUrl(UrlInterface::URL_TYPE_WEB);
                    $url = rtrim($storeBaseUrl, '/') . '/' . ltrim($page->getIdentifier(), '/');
                    $hreflang = $this->convertLocaleToHreflang($store->getCode());
                    
                    $alternateUrls[] = [
                        'hreflang' => $hreflang,
                        'url' => $url
                    ];
                }
            } catch (NoSuchEntityException $e) {
                // Page not available in this store, skip
                continue;
            }
        }

        return $alternateUrls;
    }

    /**
     * Get canonical URL for current store
     *
     * @param string $url
     * @param int|null $storeId
     * @return string
     */
    public function getCanonicalUrl(string $url, ?int $storeId = null): string
    {
        if ($storeId) {
            try {
                $store = $this->storeManager->getStore($storeId);
                $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
                // Ensure URL is absolute
                if (strpos($url, 'http') !== 0) {
                    return $baseUrl . ltrim($url, '/');
                }
            } catch (NoSuchEntityException $e) {
                // Fallback to provided URL
            }
        }
        
        return $url;
    }

    /**
     * Convert Magento locale/store code to hreflang format
     * Examples: en_US -> en, nl_NL -> nl, fr_FR -> fr
     *
     * @param string $storeCode
     * @return string
     */
    public function convertLocaleToHreflang(string $storeCode): string
    {
        // Try to get locale from store
        try {
            $store = $this->storeManager->getStore($storeCode);
            // Use the general locale configuration path
            $locale = $store->getConfig('general/locale/code');
            
            // Extract language code from locale (e.g., en_US -> en)
            if ($locale && strpos($locale, '_') !== false) {
                return strtolower(explode('_', $locale)[0]);
            }
            
            if ($locale) {
                return strtolower($locale);
            }
        } catch (NoSuchEntityException $e) {
            // Fallback to store code extraction
        }
        
        // Fallback: try to extract from store code
        if (preg_match('/^([a-z]{2})_?/i', $storeCode, $matches)) {
            return strtolower($matches[1]);
        }
        
        return strtolower($storeCode);
    }

    /**
     * Check if entity is available in store
     *
     * @param int $entityId
     * @param int $storeId
     * @param string $entityType
     * @return bool
     */
    public function isEntityAvailableInStore(int $entityId, int $storeId, string $entityType): bool
    {
        try {
            switch ($entityType) {
                case 'product':
                    $entity = $this->productRepository->getById($entityId, false, $storeId);
                    return $entity->getId() && $entity->isVisibleInSiteVisibility();
                    
                case 'category':
                    $entity = $this->categoryRepository->get($entityId, $storeId);
                    return $entity->getId() && $entity->getIsActive();
                    
                case 'cms_page':
                    $entity = $this->pageRepository->getById($entityId);
                    $storeIds = $entity->getStoreId();
                    return $storeIds == Store::DEFAULT_STORE_ID || in_array($storeId, $storeIds);
                    
                default:
                    return false;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
