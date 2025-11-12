<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Cms;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Plugin to inject CMS page identifier into resolver value for alternate URLs
 */
class InjectPageModel
{
    /**
     * Inject CMS page identifier into resolver value
     *
     * @param ResolverInterface $subject
     * @param mixed $result
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function afterResolve(
        ResolverInterface $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        // If result is an array and contains page data, ensure identifier is present
        if (is_array($result) && isset($result['identifier'])) {
            // The identifier is already in the result, which is good for our AlternateUrls resolver
            return $result;
        }

        // If value contains the page model, extract identifier
        if (is_array($value) && isset($value['model'])) {
            $page = $value['model'];
            if ($page instanceof \Magento\Cms\Model\Page) {
                if (is_array($result)) {
                    $result['identifier'] = $page->getIdentifier();
                }
            }
        }

        return $result;
    }
}
