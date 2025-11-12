<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Controller\Adminhtml\Cache;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Message\ManagerInterface as messageManager;
use HappyHorizon\PersistentGraphQlSchema\Helper\Data;
use Magento\Framework\GraphQlSchemaStitching\Common\Reader as SchemaReader;

class VerifyGraphqlSchema implements HttpGetActionInterface
{
    /**
     * @param Data $dataHelper
     * @param SchemaReader $schemaReader
     * @param messageManager $messageManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected Data              $dataHelper,
        protected SchemaReader      $schemaReader,
        protected messageManager    $messageManager,
        protected ResultFactory     $resultFactory
    ) {
    }

    /**
     * Execute verification action
     *
     * @return ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        try {
            $schema = $this->schemaReader->read();
            
            // Check for Mollie GraphQL types
            $mollieTypesFound = [];
            $checks = [
                'getMollieIdealIssuers' => 'Query',
                'setIdealIssuerOnCart' => 'Mutation',
                'ideal_issuer' => 'Cart'
            ];
            
            // Verify schema structure and check for Mollie types
            if (is_array($schema)) {
                foreach ($checks as $key => $type) {
                    if ($type === 'Query' && isset($schema['Query']['fields'][$key])) {
                        $mollieTypesFound[] = "Query.{$key}";
                    } elseif ($type === 'Mutation' && isset($schema['Mutation']['fields'][$key])) {
                        $mollieTypesFound[] = "Mutation.{$key}";
                    } elseif ($type === 'Cart' && isset($schema['Cart']['fields'][$key])) {
                        $mollieTypesFound[] = "Cart.{$key}";
                    }
                }
            }
            
            if (!empty($mollieTypesFound)) {
                $foundList = implode(', ', $mollieTypesFound);
                $this->messageManager->addSuccessMessage(
                    __("GraphQL schema verification successful. Found Mollie GraphQL types: %1", $foundList)
                );
            } else {
                $this->messageManager->addWarningMessage(
                    __("GraphQL schema verification: Mollie GraphQL types not found. " .
                       "If you recently installed the Experius_MollieGraphQl module, please flush the GraphQL schema cache.")
                );
            }
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while verifying GraphQL schema: %1', $e->getMessage())
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('adminhtml/cache');
    }
}
