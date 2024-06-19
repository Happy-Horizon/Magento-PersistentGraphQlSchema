<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Backend\Controller\Adminhtml\Cache\MassRefresh;

class Data extends AbstractHelper
{
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
        protected MassRefresh $massRefresh
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getGqlPath()
    {
        return $this->dir->getPath('etc').'/gql.php';
    }

    /**
     * @param $path
     * @return bool
     * @throws FileSystemException
     */
    public function checkIfFileExists($path)
    {
        return $this->file->isExists($path);
    }

    /**
     * @param $path
     * @return true
     * @throws FileSystemException
     */
    public function removeFile($path)
    {
        if ($this->checkIfFileExists($path)) {
            $this->file->deleteFile($path);
        }
        return true;
    }
}
