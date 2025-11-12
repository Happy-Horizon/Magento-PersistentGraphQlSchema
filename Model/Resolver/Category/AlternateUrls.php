<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver\Category;

use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;

class AlternateUrls implements ResolverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(
        protected Data $helper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            return [];
        }

        /** @var Category $category */
        $category = $value['model'];
        
        if (!$category instanceof CategoryInterface) {
            return [];
        }

        try {
            $urlKey = $category->getUrlKey();
            if (!$urlKey) {
                return [];
            }

            // Build category URL path
            $urlPath = $category->getUrlPath() ?: $category->getUrlKey();
            
            return $this->helper->getAlternateUrls($urlPath, 'category');
        } catch (\Exception $e) {
            return [];
        }
    }
}
