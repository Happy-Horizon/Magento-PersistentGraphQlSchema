<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Resolver\CmsPage;

use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\Page;

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

        /** @var Page $page */
        $page = $value['model'];
        
        if (!$page instanceof PageInterface) {
            return null;
        }

        try {
            $identifier = $page->getIdentifier();
            if (!$identifier) {
                return null;
            }

            // Build CMS page URL path
            $urlPath = $identifier;
            
            return $this->helper->getCanonicalUrl($urlPath, 'cms_page');
        } catch (\Exception $e) {
            return null;
        }
    }
}
