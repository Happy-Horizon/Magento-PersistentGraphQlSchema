<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver\Product;

use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

class CanonicalUrl implements ResolverInterface
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
            return null;
        }

        /** @var Product $product */
        $product = $value['model'];
        
        if (!$product instanceof ProductInterface) {
            return null;
        }

        try {
            $urlKey = $product->getUrlKey();
            if (!$urlKey) {
                return null;
            }

            // Build product URL path
            $urlPath = $product->getUrlKey() . $product->getUrlSuffix();
            
            return $this->helper->getCanonicalUrl($urlPath, 'product');
        } catch (\Exception $e) {
            return null;
        }
    }
}
