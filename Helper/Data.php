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

class Data extends AbstractHelper
{
    public const GQL_FILE_NAME = 'gql.php';
    /**
     * @param Context $context
     * @param DirectoryList $dir
     * @param File $file
     * @param MassRefresh $massRefresh
     */
    public function __construct(
        protected Context $context,
        protected DirectoryList $dir,
        protected File $file,
        protected MassRefresh $massRefresh,
        protected Json $json
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getGqlPath(): string
    {
        return $this->dir->getPath(DirectoryListApp::CONFIG). DIRECTORY_SEPARATOR .self::GQL_FILE_NAME;
    }

    /**
     * @param $path
     * @return bool
     * @throws FileSystemException
     */
    public function checkIfFileExists($path): bool
    {
        return $this->file->isExists($path);
    }

    /**
     * @param $path
     * @throws FileSystemException
     */
    public function removeFile($path): void
    {
        if ($this->checkIfFileExists($path)) {
            $this->file->deleteFile($path);
        }
    }

    /**
     * @param $path
     * @param $content
     * @return void
     * @throws FileSystemException
     */
    public function saveFileContent($path, $content): void
    {
        if ($this->checkIfFileExists($path)) {
            $this->file->deleteFile($path);
            $this->file->filePutContents($path, $content);
        }
        $this->file->filePutContents($path, $content);
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
}
