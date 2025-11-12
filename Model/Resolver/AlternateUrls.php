<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Resolver for alternate URLs (hreflang tags)
 */
class AlternateUrls implements ResolverInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Locale to hreflang mapping
     * @var array
     */
    private $localeToHreflangMap = [];

    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param PageRepositoryInterface $pageRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        PageRepositoryInterface $pageRepository,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
        $this->scopeConfig = $scopeConfig;
        $this->pageRepository = $pageRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($value)) {
            return [];
        }

        $currentStoreId = $context->getExtensionAttributes()->getStore()->getId();
        $alternateUrls = [];

        try {
            $stores = $this->storeRepository->getList();
            
            foreach ($stores as $store) {
                // Skip if store is not active
                if (!$store->isActive()) {
                    continue;
                }

                // Skip current store
                if ($store->getId() == $currentStoreId) {
                    continue;
                }

                $url = $this->getEntityUrl($value, $store->getId());
                
                if ($url && $this->isValidUrl($url)) {
                    $hreflang = $this->getHreflangCode($store->getId());
                    
                    if ($hreflang) {
                        $alternateUrls[] = [
                            'href' => $url,
                            'hreflang' => $hreflang
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Return empty array on error
            return [];
        }

        return $alternateUrls;
    }

    /**
     * Get entity URL for a specific store
     *
     * @param array $value
     * @param int $storeId
     * @return string|null
     */
    private function getEntityUrl(array $value, int $storeId): ?string
    {
        try {
            // Handle CMS Page - check for identifier or model
            if (isset($value['identifier'])) {
                return $this->getCmsPageUrl($value['identifier'], $storeId);
            }
            
            // Check if value contains a CMS page model
            if (isset($value['model']) && $value['model'] instanceof \Magento\Cms\Model\Page) {
                $identifier = $value['model']->getIdentifier();
                return $this->getCmsPageUrl($identifier, $storeId);
            }

            // Handle Product - check for sku, url_key, or model
            if (isset($value['sku']) || isset($value['url_key'])) {
                return $this->getProductUrl($value, $storeId);
            }
            
            // Check if value contains a product model
            if (isset($value['model']) && $value['model'] instanceof \Magento\Catalog\Model\Product) {
                $sku = $value['model']->getSku();
                return $this->getProductUrl(['sku' => $sku], $storeId);
            }

            // Handle Category - check for id, url_key, or model
            if (isset($value['id']) || isset($value['url_key'])) {
                return $this->getCategoryUrl($value, $storeId);
            }
            
            // Check if value contains a category model
            if (isset($value['model']) && $value['model'] instanceof \Magento\Catalog\Model\Category) {
                $categoryId = $value['model']->getId();
                return $this->getCategoryUrl(['id' => $categoryId], $storeId);
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Get CMS page URL
     *
     * @param string $identifier
     * @param int $storeId
     * @return string|null
     */
    private function getCmsPageUrl(string $identifier, int $storeId): ?string
    {
        try {
            $page = $this->pageRepository->getById($identifier, false, $storeId);
            if ($page->getId() && $page->isActive()) {
                $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK);
                return rtrim($baseUrl, '/') . '/' . $identifier;
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return null;
    }

    /**
     * Get product URL
     *
     * @param array $value
     * @param int $storeId
     * @return string|null
     */
    private function getProductUrl(array $value, int $storeId): ?string
    {
        try {
            $sku = $value['sku'] ?? null;
            if (!$sku && isset($value['url_key'])) {
                // If we only have url_key, we need to find the product
                // This is a simplified approach - in production you might want to use a repository
                return null;
            }

            if ($sku) {
                $product = $this->productRepository->get($sku, false, $storeId);
                if ($product->getId() && $product->isAvailable()) {
                    // Use Magento's URL model to get proper URL with rewrites
                    $url = $product->getUrlModel()->getUrl($product, ['_scope' => $storeId]);
                    return $url;
                }
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return null;
    }

    /**
     * Get category URL
     *
     * @param array $value
     * @param int $storeId
     * @return string|null
     */
    private function getCategoryUrl(array $value, int $storeId): ?string
    {
        try {
            $categoryId = $value['id'] ?? null;
            if (!$categoryId && isset($value['url_key'])) {
                // If we only have url_key, we need to find the category
                return null;
            }

            if ($categoryId) {
                $category = $this->categoryRepository->get($categoryId, $storeId);
                if ($category->getId() && $category->getIsActive()) {
                    // Use Magento's URL model to get proper URL with rewrites
                    $category->setStoreId($storeId);
                    $url = $category->getUrl();
                    return $url;
                }
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return null;
    }

    /**
     * Get hreflang code from store locale
     *
     * @param int $storeId
     * @return string|null
     */
    private function getHreflangCode(int $storeId): ?string
    {
        if (isset($this->localeToHreflangMap[$storeId])) {
            return $this->localeToHreflangMap[$storeId];
        }

        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if ($locale) {
                $hreflang = $this->convertLocaleToHreflang($locale);
                $this->localeToHreflangMap[$storeId] = $hreflang;
                return $hreflang;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Convert Magento locale to hreflang code
     *
     * @param string $locale
     * @return string
     */
    private function convertLocaleToHreflang(string $locale): string
    {
        // Convert locale format (e.g., "nl_NL", "en_US") to hreflang format (e.g., "nl", "en")
        $parts = explode('_', $locale);
        $language = strtolower($parts[0] ?? '');
        
        // Handle special cases
        $mapping = [
            'nl' => 'nl',
            'en' => 'en',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
            'it' => 'it',
        ];

        return $mapping[$language] ?? $language;
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
