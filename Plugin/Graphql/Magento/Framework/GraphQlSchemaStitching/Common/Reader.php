<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQlSchemaStitching\Common;

use Magento\Framework\Filesystem\DirectoryList;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

class Reader
{
    /**
     * @param DirectoryList $dir
     */
    public function __construct(
        protected DirectoryList $dir,
    ) {
    }

    /**
     * @param \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject
     * @param \Closure $proceed
     * @param $scope
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws FilesystemException
     * @throws JsonException
     */
    public function aroundRead(
        \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject,
        \Closure $proceed,
        $scope = null
    ): array {
        $filename = $this->dir->getPath('etc') . '/gql.php';

        try {
            $data = \Safe\file_get_contents($filename);
        } catch (\Exception $e) {
            $data = false;
        }

        if (false === $data || '' === (string)$data) {
            $data = $proceed();
            \Safe\file_put_contents($filename, \Safe\json_encode($data));
            return $data;
        } else {
            return json_decode($data, true);
        }
    }
}