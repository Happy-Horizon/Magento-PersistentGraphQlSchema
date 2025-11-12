<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use HappyHorizon\HreflangGraphQl\Model\HreflangUrlGenerator;

/**
 * Resolver for Category hreflang field
 */
class Hreflang implements ResolverInterface
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
            $category = $value['model'];
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

            return $this->hreflangUrlGenerator->getCurrentHreflang($storeId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
