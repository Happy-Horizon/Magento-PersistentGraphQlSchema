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
     * @param $scope
     * @return array
     */
    public function aroundRead(
        \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject,
        \Closure $proceed,
        $scope = null
    ): array {
        $filename = $this->helper->getGqlPath();

        try {
            $data = $this->helper->checkIfFileExists($filename)? $this->helper->getFileContent($filename): false;
        } catch (\Exception $e) {
            $this->logger->error(
                'PersistentGraphQlSchema: Error reading cached schema file: ' . $e->getMessage(),
                ['exception' => $e, 'filename' => $filename]
            );
            $data = false;
        }

        if (false === $data || '' === (string)$data) {
            // No cached schema exists, generate and save it
            try {
                $data = $proceed();
                $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                $this->logger->info('PersistentGraphQlSchema: Generated and cached new GraphQL schema');
                return $data;
            } catch (\Exception $e) {
                $this->logger->error(
                    'PersistentGraphQlSchema: Error generating schema: ' . $e->getMessage(),
                    ['exception' => $e]
                );
                // Fallback to original behavior if caching fails
                return $proceed();
            }
        } else {
            // Decode cached schema
            try {
                $decodedData = $this->helper->decodeData($data);
                
                if (!is_array($decodedData)) {
                    $this->logger->warning(
                        'PersistentGraphQlSchema: Cached schema data is invalid, regenerating'
                    );
                    $this->helper->removeFile($filename);
                    $data = $proceed();
                    $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                    return $data;
                }
                
                // Validate that cached schema includes required extensions (e.g., configurator_options)
                if (!$this->helper->validateSchemaIncludesConfigurator($decodedData)) {
                    // Schema is stale, regenerate it
                    $this->logger->info(
                        'PersistentGraphQlSchema: Cached schema is stale, regenerating with latest extensions'
                    );
                    $this->helper->removeFile($filename);
                    $data = $proceed();
                    $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                    return $data;
                }
                
                return $decodedData;
            } catch (\Exception $e) {
                $this->logger->error(
                    'PersistentGraphQlSchema: Error decoding cached schema: ' . $e->getMessage(),
                    ['exception' => $e]
                );
                // Fallback to regenerating schema
                $this->helper->removeFile($filename);
                return $proceed();
            }
        }
    }
}