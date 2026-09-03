<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQlSchemaStitching\Common;

use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\Filesystem\DirectoryList;

class Reader
{
    /**
     * @param DirectoryList $dir
     * @param Data $helper
     */
    public function __construct(
        protected DirectoryList $dir,
        protected Data $helper
    ) {
    }

    /**
     * @param \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject
     * @param \Closure $proceed
     * @param string|null $scope
     * @return array
     */
    public function aroundRead(
        \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject,
        \Closure $proceed,
        ?string $scope = null
    ): array {
        $filename = $this->helper->getGqlPath();

        try {
            $data = $this->helper->checkIfFileExists($filename)
                ? $this->helper->getFileContent($filename)
                : false;
        } catch (\Exception $e) {
            $data = false;
        }

        if (false === $data || '' === $data) {
            $schema = $proceed($scope);
            $this->helper->saveFileContent($filename, $this->helper->encodeData($schema));
            return $schema;
        }

        $decoded = $this->helper->decodeData($data);
        return is_array($decoded) ? $decoded : [];
    }
}
