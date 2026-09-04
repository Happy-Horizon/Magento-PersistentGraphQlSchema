<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
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
use Magento\Framework\Serialize\Serializer\Json;

class Data extends AbstractHelper
{
    public const GQL_FILE_NAME = 'gql.php';

    /**
     * @param Context $context
     * @param DirectoryList $dir
     * @param File $file
     * @param Json $json
     */
    public function __construct(
        Context $context,
        protected DirectoryList $dir,
        protected File $file,
        protected Json $json
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getGqlPath(): string
    {
        try {
            return $this->dir->getPath(DirectoryListApp::CONFIG) . DIRECTORY_SEPARATOR . self::GQL_FILE_NAME;
        } catch (FileSystemException $e) {
            return '';
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function checkIfFileExists(string $path): bool
    {
        try {
            return $this->file->isExists($path);
        } catch (FileSystemException $e) {
            return false;
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function removeFile(string $path): bool
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
     * @param string $path
     * @param string $content
     * @return bool
     */
    public function saveFileContent(string $path, string $content): bool
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
     * @param string $path
     * @return string
     */
    public function getFileContent(string $path): string
    {
        try {
            if ($this->checkIfFileExists($path)) {
                return $this->file->fileGetContents($path);
            }
        } catch (FileSystemException $e) {
            return '';
        }
        return '';
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function encodeData(mixed $data): string
    {
        return $this->json->serialize($data);
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function decodeData(string $data): mixed
    {
        return $this->json->unserialize($data);
    }
}
