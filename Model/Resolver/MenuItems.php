<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class MenuItems implements ResolverInterface
{
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
        // This resolver handles the menu_items field on ContentBlockDynamic
        if (isset($value['menu_items']) && is_array($value['menu_items'])) {
            return $value['menu_items'];
        }
        
        // This resolver also handles fields on MenuItems type (label, url, target, children)
        if (isset($value['label']) || isset($value['url']) || isset($value['target']) || isset($value['children'])) {
            return $value[$field->getName()] ?? null;
        }
        
        return [];
    }
}
