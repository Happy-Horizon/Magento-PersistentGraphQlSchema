<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQlSchemaStitching\Common;

use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class Reader
{
    /**
     * @param DirectoryList $dir
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected DirectoryList $dir,
        protected Data $helper,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject
     * @param \Closure $proceed
     * @param mixed $scope
     * @return array
     */
    public function aroundRead(
        \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject,
        \Closure $proceed,
        $scope = null
    ): array {
        $filename = $this->helper->getGqlPath();

        try {
            $data = $this->helper->checkIfFileExists($filename) ? $this->helper->getFileContent($filename) : false;
        } catch (\Exception $e) {
            $this->logger->info(
                'Failed to read cached GraphQL schema file: ' . $e->getMessage(),
                ['exception' => $e, 'filename' => $filename]
            );
            $data = false;
        }

        if (false === $data || '' === (string)$data) {
            // Regenerate schema from source files (includes new hreflang/canonical URL changes)
            $data = $proceed();
            try {
                $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
            } catch (\Exception $e) {
                $this->logger->warning(
                    'Failed to save cached GraphQL schema file: ' . $e->getMessage(),
                    ['exception' => $e, 'filename' => $filename]
                );
            }
            return $data;
        } else {
            try {
                return $this->helper->decodeData($data);
            } catch (\Exception $e) {
                // If decoding fails (e.g., schema structure changed), regenerate
                $this->logger->info(
                    'Failed to decode cached GraphQL schema, regenerating: ' . $e->getMessage(),
                    ['exception' => $e, 'filename' => $filename]
                );
                $data = $proceed();
                try {
                    $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                } catch (\Exception $saveException) {
                    $this->logger->warning(
                        'Failed to save regenerated GraphQL schema file: ' . $saveException->getMessage(),
                        ['exception' => $saveException, 'filename' => $filename]
                    );
                }
                return $data;
            }
        }
    }
}