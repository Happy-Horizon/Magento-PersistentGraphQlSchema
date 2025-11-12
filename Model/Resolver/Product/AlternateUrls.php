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
use Magento\Store\Model\StoreManagerInterface;

class AlternateUrls implements ResolverInterface
{
    /**
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected Data $helper,
        protected StoreManagerInterface $storeManager
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

        /** @var Product $product */
        $product = $value['model'];
        
        if (!$product instanceof ProductInterface) {
            return [];
        }

        try {
            $urlKey = $product->getUrlKey();
            if (!$urlKey) {
                return [];
            }

            // Build product URL path
            $urlPath = $product->getUrlKey() . $product->getUrlSuffix();
            
            return $this->helper->getAlternateUrls($urlPath, 'product');
        } catch (\Exception $e) {
            return [];
        }
    }
}
