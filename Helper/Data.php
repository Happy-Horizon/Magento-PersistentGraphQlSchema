<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\TestFramework\Event\Magento;

class Data extends AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
    public function tempfunction()
    {
        return MAGENTO_BP;
    }


}
