<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Controller\Adminhtml\Cache;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use HappyHorizon\PersistentGraphQlSchema\Helper\Data;

class CleanGraphql implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $dataHelper;
    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory, Data $dataHelper)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $temp = $this->dataHelper->tempfunction();
        var_dump($temp);
        die();
        return $this->resultPageFactory->create();
    }






}
