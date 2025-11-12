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
use Magento\Framework\View\Result\PageFactory;
use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\Message\ManagerInterface as messageManager;
use Magento\Backend\Model\View\Result\Redirect;

class CleanGraphql implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Data $dataHelper
     * @param TypeListInterface $typeList
     * @param CacheManager $cacheManager
     * @param messageManager $messageManager
     * @param Redirect $redirect
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected PageFactory       $resultPageFactory,
        protected Data              $dataHelper,
        protected TypeListInterface $typeList,
        protected CacheManager      $cacheManager,
        protected messageManager    $messageManager,
        protected Redirect          $redirect,
        protected ResultFactory     $resultFactory
    ) {
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        $this->dataHelper->removeFile($this->dataHelper->getGqlPath());
        try {
            $types = ['config'];
            $updatedTypes = 0;
            
            // Flush graphql_schema cache type if available
            if ($this->cacheManager->isEnabled('graphql_schema')) {
                $this->typeList->cleanType('graphql_schema');
                $updatedTypes++;
            }
            
            foreach ($types as $type) {
                $this->typeList->cleanType($type);
                $updatedTypes++;
            }
            
            if ($updatedTypes > 0) {
                $this->messageManager->addSuccessMessage(
                    __("Flushed the GraphQL schema files and refreshed the cache. " .
                       "GraphQL schema will be regenerated on next request, including any new types " .
                       "from modules like Mollie GraphQL.")
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while refreshing cache.'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('adminhtml/cache');

    }
}
