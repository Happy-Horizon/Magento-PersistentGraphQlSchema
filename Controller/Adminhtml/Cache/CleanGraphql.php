<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Controller\Adminhtml\Cache;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\Redirect;

class CleanGraphql implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Data $dataHelper
     * @param TypeListInterface $typeList
     * @param ManagerInterface $messageManager
     * @param Redirect $redirect
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected PageFactory $resultPageFactory,
        protected Data $dataHelper,
        protected TypeListInterface $typeList,
        protected ManagerInterface $messageManager,
        protected Redirect $redirect,
        protected ResultFactory $resultFactory
    ) {
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $this->dataHelper->removeFile($this->dataHelper->getGqlPath());
        try {
            $types = ['config'];
            $updatedTypes = 0;
            foreach ($types as $type) {
                $this->typeList->cleanType($type);
                $updatedTypes++;
            }
            if ($updatedTypes > 0) {
                $this->messageManager->addSuccessMessage(__("Flushed the GraphqlSchema files and refreshed the config cache"));
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
