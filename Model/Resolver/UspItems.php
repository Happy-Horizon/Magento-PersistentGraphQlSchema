<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use HappyHorizon\PersistentGraphQlSchema\Model\DataProvider\Block;

class UspItems implements ResolverInterface
{
    /**
     * @param Block $dataProvider
     */
    public function __construct(
        protected Block $dataProvider
    ) {
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!isset($value['usp_items']) || !is_array($value['usp_items'])) {
            return [];
        }

        // Ensure link and url are set for each USP item
        $formatted = [];
        foreach ($value['usp_items'] as $item) {
            $formattedItem = [
                'identifier' => $item['identifier'] ?? '',
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'link' => $item['link'] ?? '',
                'url' => $item['url'] ?? '',
            ];

            // Recursively handle nested usp_items
            if (isset($item['usp_items']) && is_array($item['usp_items'])) {
                $formattedItem['usp_items'] = $this->dataProvider->formatUspItems($item['usp_items']);
            } else {
                $formattedItem['usp_items'] = [];
            }

            $formatted[] = $formattedItem;
        }

        return $formatted;
    }
}
