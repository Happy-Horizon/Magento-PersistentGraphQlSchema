<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver\CmsPage;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use HappyHorizon\PersistentGraphQlSchema\Helper\AlternateUrlHelper;
use Magento\Store\Model\StoreManagerInterface;

class AlternateUrls implements ResolverInterface
{
    /**
     * @param AlternateUrlHelper $alternateUrlHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected AlternateUrlHelper $alternateUrlHelper,
        protected StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['page_id'])) {
            return [];
        }

        $pageId = (int)$value['page_id'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        try {
            return $this->alternateUrlHelper->getCmsPageAlternateUrls($pageId, $storeId);
        } catch (\Exception $e) {
            return [];
        }
    }
}
