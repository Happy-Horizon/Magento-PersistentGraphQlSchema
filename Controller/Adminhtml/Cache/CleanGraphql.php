<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Controller\Adminhtml\Cache;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;

class CleanGraphql implements HttpGetActionInterface
{
    /**
     * @param Data $dataHelper
     * @param TypeListInterface $typeList
     * @param MessageManager $messageManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected Data              $dataHelper,
        protected TypeListInterface $typeList,
        protected MessageManager    $messageManager,
        protected ResultFactory     $resultFactory
    ) {
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     * @throws FileSystemException
     */
    public function execute(): ResultInterface
    {
        $gqlPath = $this->dataHelper->getGqlPath();
        $fileRemoved = $this->dataHelper->removeFile($gqlPath);
        
        try {
            $types = ['config'];
            $updatedTypes = 0;
            foreach ($types as $type) {
                $this->typeList->cleanType($type);
                $updatedTypes++;
            }
            if ($updatedTypes > 0) {
                $message = $fileRemoved 
                    ? __("Flushed the GraphQL schema cache file and refreshed the config cache. Schema will be regenerated on next request.")
                    : __("Refreshed the config cache. GraphQL schema cache file was not found or already removed.");
                $this->messageManager->addSuccessMessage($message);
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while refreshing cache.'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('adminhtml/*');
    }
}
