<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Helper;

use Magento\Framework\App\Filesystem\DirectoryList as DirectoryListApp;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Backend\Controller\Adminhtml\Cache\MassRefresh;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;

class Data extends AbstractHelper
{
    public const GQL_FILE_NAME = 'gql.php';
    /**
     * @param Context $context
     * @param DirectoryList $dir
     * @param File $file
     * @param MassRefresh $massRefresh
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        protected Context $context,
        protected DirectoryList $dir,
        protected File $file,
        protected MassRefresh $massRefresh,
        protected Json $json,
        protected StoreManagerInterface $storeManager,
        protected UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getGqlPath(): string
    {
        try {
            return $this->dir->getPath(DirectoryListApp::CONFIG). DIRECTORY_SEPARATOR .self::GQL_FILE_NAME;
        } catch (FileSystemException $e) {
            return '';
        }
    }

    /**
     * @param $path
     * @return bool
     */
    public function checkIfFileExists($path): bool
    {
        try {
            return $this->file->isExists($path);
        } catch (FileSystemException $e) {
            return false;
        }
    }

    /**
     * @param $path
     * @return bool
     */
    public function removeFile($path): bool
    {
        try {
            if ($this->checkIfFileExists($path)) {
                $this->file->deleteFile($path);
                return true;
            }
        } catch (FileSystemException $e) {
            return false;
        }
        return false;
    }

    /**
     * @param $path
     * @param $content
     * @return bool
     */
    public function saveFileContent($path, $content): bool
    {
        try {
            if ($this->checkIfFileExists($path)) {
                $this->removeFile($path);
            }
            $this->file->filePutContents($path, $content);
        } catch (FileSystemException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param $path
     * @return string
     */
    public function getFileContent($path): string
    {
        try {
            if ($this->checkIfFileExists($path)) {
                return $this->file->fileGetContents($path);
            }
        } catch (FileSystemException $e) {
            return "";
        }
        return "";
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function encodeData($data): bool|string
    {
        return $this->json->serialize($data);
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function decodeData($data): mixed
    {
        return $this->json->unserialize($data);
    }

    /**
     * Get alternate URLs for hreflang tags
     *
     * @param string $urlPath The URL path (e.g., product URL key, category URL key, CMS page identifier)
     * @param string|null $entityType Type of entity: 'product', 'category', or 'cms_page'
     * @return array Array of alternate URLs with hreflang structure: [['hreflang' => 'en', 'href' => 'url'], ...]
     */
    public function getAlternateUrls(string $urlPath, ?string $entityType = null): array
    {
        $alternateUrls = [];
        
        try {
            $currentStore = $this->storeManager->getStore();
            $stores = $this->storeManager->getStores();
            
            foreach ($stores as $store) {
                if (!$store->getIsActive()) {
                    continue;
                }
                
                $storeCode = $store->getCode();
                $localeCode = $this->getLocaleCode($store);
                
                try {
                    $storeUrl = $this->buildEntityUrl($store, $urlPath, $entityType);
                    
                    if ($storeUrl) {
                        $alternateUrls[] = [
                            'hreflang' => $localeCode,
                            'href' => $storeUrl
                        ];
                    }
                } catch (\Exception $e) {
                    $this->context->getLogger()->error(
                        sprintf('Error generating alternate URL for store %s: %s', $storeCode, $e->getMessage())
                    );
                }
            }
        } catch (\Exception $e) {
            $this->context->getLogger()->error(
                sprintf('Error generating alternate URLs: %s', $e->getMessage())
            );
        }
        
        return $alternateUrls;
    }

    /**
     * Get canonical URL for the current store
     *
     * @param string $urlPath The URL path (e.g., product URL key, category URL key, CMS page identifier)
     * @param string|null $entityType Type of entity: 'product', 'category', or 'cms_page'
     * @return string|null Canonical URL or null if unable to generate
     */
    public function getCanonicalUrl(string $urlPath, ?string $entityType = null): ?string
    {
        try {
            $currentStore = $this->storeManager->getStore();
            return $this->buildEntityUrl($currentStore, $urlPath, $entityType);
        } catch (\Exception $e) {
            $this->context->getLogger()->error(
                sprintf('Error generating canonical URL: %s', $e->getMessage())
            );
            return null;
        }
    }

    /**
     * Build URL for a specific entity type and store
     *
     * @param Store $store
     * @param string $urlPath
     * @param string|null $entityType
     * @return string|null
     */
    protected function buildEntityUrl(Store $store, string $urlPath, ?string $entityType): ?string
    {
        try {
            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            $baseUrl = rtrim($baseUrl, '/');
            
            // Remove query parameters and fragments from URL path
            $urlPath = preg_replace('/[?#].*$/', '', $urlPath);
            $urlPath = ltrim($urlPath, '/');
            
            // Normalize URL path based on entity type
            if ($entityType === 'cms_page') {
                // CMS pages typically don't need a prefix, but ensure proper format
                if (!empty($urlPath)) {
                    $urlPath = ltrim($urlPath, '/');
                }
            } elseif ($entityType === 'category') {
                // Categories may have path structure
                if (!empty($urlPath)) {
                    $urlPath = ltrim($urlPath, '/');
                }
            } elseif ($entityType === 'product') {
                // Products typically have .html suffix, but it should already be in urlPath
                if (!empty($urlPath)) {
                    $urlPath = ltrim($urlPath, '/');
                }
            }
            
            // Build full URL
            $fullUrl = $baseUrl . '/' . $urlPath;
            
            // Ensure proper URL format
            return filter_var($fullUrl, FILTER_VALIDATE_URL) ? $fullUrl : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get locale code for a store (e.g., 'en-US', 'nl-NL')
     *
     * @param Store $store
     * @return string
     */
    protected function getLocaleCode(Store $store): string
    {
        try {
            $locale = $store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);
            if ($locale) {
                return str_replace('_', '-', $locale);
            }
        } catch (\Exception $e) {
            // Fallback to store code if locale config is not available
        }
        
        // Fallback: use store code or default to 'en'
        return $store->getCode() ?: 'en';
    }
}
