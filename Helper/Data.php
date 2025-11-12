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
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    public const GQL_FILE_NAME = 'gql.php';
    public const CONFIGURATOR_MODULE_NAME = 'HappyHorizon_ConfiguratorGraphQl';
    
    /**
     * @param Context $context
     * @param DirectoryList $dir
     * @param File $file
     * @param MassRefresh $massRefresh
     * @param Json $json
     * @param ModuleManager $moduleManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected DirectoryList $dir,
        protected File $file,
        protected MassRefresh $massRefresh,
        protected Json $json,
        protected ModuleManager $moduleManager,
        protected LoggerInterface $logger
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
     * Check if ConfiguratorGraphQl module is enabled
     *
     * @return bool
     */
    public function isConfiguratorModuleEnabled(): bool
    {
        return $this->moduleManager->isEnabled(self::CONFIGURATOR_MODULE_NAME);
    }

    /**
     * Validate if cached schema includes configurator extensions
     *
     * @param array $schema
     * @return bool
     */
    public function validateSchemaIncludesConfigurator(array $schema): bool
    {
        // If configurator module is not enabled, schema is valid
        if (!$this->isConfiguratorModuleEnabled()) {
            return true;
        }

        // Check if CartItemInput type exists and includes configurator_options field
        // Schema structure can vary, so check multiple possible locations
        $cartItemInputFound = false;
        $configuratorOptionsFound = false;

        // Check direct access
        if (isset($schema['CartItemInput'])) {
            $cartItemInputFound = true;
            if (isset($schema['CartItemInput']['fields']['configurator_options'])) {
                $configuratorOptionsFound = true;
            }
        }

        // Also check if schema is nested differently (some Magento versions)
        if (!$cartItemInputFound) {
            foreach ($schema as $key => $value) {
                if (is_array($value) && isset($value['name']) && $value['name'] === 'CartItemInput') {
                    $cartItemInputFound = true;
                    if (isset($value['fields']['configurator_options'])) {
                        $configuratorOptionsFound = true;
                    }
                    break;
                }
            }
        }

        if ($cartItemInputFound && $configuratorOptionsFound) {
            return true;
        }

        // Schema is missing configurator extensions
        if ($cartItemInputFound) {
            $this->logger->warning(
                'PersistentGraphQlSchema: Cached schema is missing configurator_options field in CartItemInput. ' .
                'Schema will be regenerated.'
            );
        } else {
            $this->logger->warning(
                'PersistentGraphQlSchema: Cached schema structure unexpected (CartItemInput not found). ' .
                'Schema will be regenerated.'
            );
        }
        return false;
    }
}
