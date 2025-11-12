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

class UspItems implements ResolverInterface
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
        // This resolver handles the usp_items field on ContentBlockDynamic
        if (isset($value['usp_items']) && is_array($value['usp_items'])) {
            return $value['usp_items'];
        }
        
        // This resolver also handles fields on UspItems type (label, icon, description)
        if (isset($value['label']) || isset($value['icon']) || isset($value['description'])) {
            return $value[$field->getName()] ?? null;
        }
        
        return [];
    }
}
