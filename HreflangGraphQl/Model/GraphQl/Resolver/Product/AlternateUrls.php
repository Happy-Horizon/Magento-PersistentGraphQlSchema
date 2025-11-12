<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use HappyHorizon\HreflangGraphQl\Model\HreflangUrlGenerator;

/**
 * Resolver for Product alternate_urls field
 */
class AlternateUrls implements ResolverInterface
{
    /**
     * @param HreflangUrlGenerator $hreflangUrlGenerator
     */
    public function __construct(
        private readonly HreflangUrlGenerator $hreflangUrlGenerator
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

        try {
            $product = $value['model'];
            $productId = (int)$product->getId();
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

            $alternateUrls = $this->hreflangUrlGenerator->getProductAlternateUrls($productId, $storeId);

            return $this->hreflangUrlGenerator->formatAlternateUrls($alternateUrls);
        } catch (\Exception $e) {
            return null;
        }
    }
}
