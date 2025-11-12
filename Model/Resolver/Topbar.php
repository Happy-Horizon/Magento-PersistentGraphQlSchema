<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver;

use HappyHorizon\PersistentGraphQlSchema\Model\DataProvider\Block as BlockDataProvider;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Topbar implements ResolverInterface
{
    /**
     * @param BlockDataProvider $blockDataProvider
     */
    public function __construct(
        protected BlockDataProvider $blockDataProvider
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
        if (!isset($args['identifier']) || empty($args['identifier'])) {
            return null;
        }

        $block = $this->blockDataProvider->getBlockByIdentifier($args['identifier']);
        
        if (!$block) {
            return null;
        }

        return [
            'identifier' => $block->getIdentifier(),
            'title' => $block->getTitle(),
            'content' => $block->getContent(),
            'link' => $this->blockDataProvider->getLink($block),
            'url' => $this->blockDataProvider->getUrl($block),
            'menu_items' => $this->blockDataProvider->getMenuItems($block),
            'usp_items' => $this->blockDataProvider->getUspItems($block)
        ];
    }
}
