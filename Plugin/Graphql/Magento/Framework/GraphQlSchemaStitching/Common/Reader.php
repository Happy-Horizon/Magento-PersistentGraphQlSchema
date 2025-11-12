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

        $this->logger->info('GraphQL Schema Reader: Starting schema read operation', [
            'scope' => $scope,
            'filename' => $filename
        ]);

        try {
            $data = $this->helper->checkIfFileExists($filename) ? $this->helper->getFileContent($filename) : false;
        } catch (\Exception $e) {
            $this->logger->warning('GraphQL Schema Reader: Error checking file existence', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            $data = false;
        }

        if (false === $data || '' === (string)$data) {
            $this->logger->info('GraphQL Schema Reader: Schema file not found, generating new schema');
            $data = $proceed();
            $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
            $this->logger->info('GraphQL Schema Reader: New schema generated and saved');
            return $data;
        } else {
            try {
                $decodedData = $this->helper->decodeData($data);
                
                // Validate schema contains required fields
                $validationResult = $this->validateSchemaFields($decodedData);
                
                if (!$validationResult['valid']) {
                    $this->logger->warning('GraphQL Schema Reader: Schema validation failed, regenerating', [
                        'errors' => $validationResult['errors']
                    ]);
                    // Regenerate schema on validation failure
                    $data = $proceed();
                    $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                    $this->logger->info('GraphQL Schema Reader: Schema regenerated after validation failure');
                    return $data;
                }
                
                $this->logger->info('GraphQL Schema Reader: Schema loaded from cache successfully');
                return $decodedData;
            } catch (\Exception $e) {
                $this->logger->error('GraphQL Schema Reader: Decode failure, regenerating schema', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Regenerate schema on decode failure
                $data = $proceed();
                $this->helper->saveFileContent($filename, $this->helper->encodeData($data));
                $this->logger->info('GraphQL Schema Reader: Schema regenerated after decode failure');
                return $data;
            }
        }
    }

    /**
     * Validate that schema contains alternate_urls and canonical_url fields
     *
     * @param array $schemaData
     * @return array ['valid' => bool, 'errors' => array]
     */
    protected function validateSchemaFields(array $schemaData): array
    {
        $errors = [];
        $requiredFields = ['alternate_urls', 'canonical_url'];
        $requiredTypes = ['ProductInterface', 'CategoryInterface', 'CmsPage'];
        
        // Check if schema data structure is valid
        if (!isset($schemaData['types']) || !is_array($schemaData['types'])) {
            $errors[] = 'Schema types structure is invalid';
            return ['valid' => false, 'errors' => $errors];
        }
        
        $types = $schemaData['types'];
        $foundFields = [];
        
        // Check each required type
        foreach ($requiredTypes as $typeName) {
            if (!isset($types[$typeName])) {
                $errors[] = sprintf('Required type %s not found in schema', $typeName);
                continue;
            }
            
            $type = $types[$typeName];
            
            // Check if type has fields
            if (!isset($type['fields']) || !is_array($type['fields'])) {
                $errors[] = sprintf('Type %s has no fields defined', $typeName);
                continue;
            }
            
            $fields = $type['fields'];
            
            // Check for required fields
            foreach ($requiredFields as $fieldName) {
                if (isset($fields[$fieldName])) {
                    $foundFields[] = sprintf('%s.%s', $typeName, $fieldName);
                } else {
                    $errors[] = sprintf('Field %s not found in type %s', $fieldName, $typeName);
                }
            }
        }
        
        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }
        
        $this->logger->debug('GraphQL Schema Reader: Schema validation passed', [
            'found_fields' => $foundFields
        ]);
        
        return ['valid' => true, 'errors' => []];
    }
}