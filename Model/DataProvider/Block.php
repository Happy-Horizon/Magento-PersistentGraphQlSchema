<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\DataProvider;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Store\Model\StoreManagerInterface;

class Block
{
    /**
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory $blockFactory
     * @param StoreManagerInterface $storeManager
     * @param Json $jsonSerializer
     * @param Serialize $serializeSerializer
     */
    public function __construct(
        protected BlockRepositoryInterface $blockRepository,
        protected BlockFactory $blockFactory,
        protected StoreManagerInterface $storeManager,
        protected Json $jsonSerializer,
        protected Serialize $serializeSerializer
    ) {
    }

    /**
     * Get CMS block by identifier
     *
     * @param string $identifier
     * @return BlockInterface|null
     */
    public function getBlockByIdentifier(string $identifier): ?BlockInterface
    {
        try {
            // Try to get by ID if identifier is numeric
            if (is_numeric($identifier)) {
                return $this->blockRepository->getById((int)$identifier);
            }
            
            // Load by identifier using block factory
            $block = $this->blockFactory->create();
            $storeId = (int)$this->storeManager->getStore()->getId();
            $block->setStoreId($storeId);
            $block->load($identifier, 'identifier');
            
            if ($block->getId()) {
                return $this->blockRepository->getById($block->getId());
            }
            
            return null;
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract menu_items from CMS block attributes
     *
     * @param BlockInterface $block
     * @return array
     */
    public function getMenuItems(BlockInterface $block): array
    {
        $menuItems = [];
        
        // Try to get menu_items from custom attributes or content
        $attributes = $block->getData();
        
        // Check for menu_items attribute
        if (isset($attributes['menu_items'])) {
            $menuItemsData = $attributes['menu_items'];
            $menuItems = $this->parseMenuItems($menuItemsData);
        }
        
        // Also check content for JSON/serialized data
        $content = $block->getContent();
        if ($content) {
            $parsedContent = $this->parseContentForMenuItems($content);
            if (!empty($parsedContent)) {
                $menuItems = array_merge($menuItems, $parsedContent);
            }
        }
        
        return $menuItems;
    }

    /**
     * Extract usp_items from CMS block attributes
     *
     * @param BlockInterface $block
     * @return array
     */
    public function getUspItems(BlockInterface $block): array
    {
        $uspItems = [];
        
        // Try to get usp_items from custom attributes or content
        $attributes = $block->getData();
        
        // Check for usp_items attribute
        if (isset($attributes['usp_items'])) {
            $uspItemsData = $attributes['usp_items'];
            $uspItems = $this->parseUspItems($uspItemsData);
        }
        
        // Also check content for JSON/serialized data
        $content = $block->getContent();
        if ($content) {
            $parsedContent = $this->parseContentForUspItems($content);
            if (!empty($parsedContent)) {
                $uspItems = array_merge($uspItems, $parsedContent);
            }
        }
        
        return $uspItems;
    }

    /**
     * Parse menu items from various formats (JSON, serialized, array)
     *
     * @param mixed $data
     * @return array
     */
    protected function parseMenuItems($data): array
    {
        if (empty($data)) {
            return [];
        }

        // If already an array, return as is
        if (is_array($data)) {
            return $this->formatMenuItems($data);
        }

        // Try JSON decode
        if (is_string($data)) {
            // Try JSON first
            $jsonData = $this->jsonSerializer->unserialize($data);
            if (is_array($jsonData)) {
                return $this->formatMenuItems($jsonData);
            }

            // Try PHP serialize
            try {
                $serializedData = $this->serializeSerializer->unserialize($data);
                if (is_array($serializedData)) {
                    return $this->formatMenuItems($serializedData);
                }
            } catch (\Exception $e) {
                // Not serialized, continue
            }
        }

        return [];
    }

    /**
     * Parse USP items from various formats (JSON, serialized, array)
     *
     * @param mixed $data
     * @return array
     */
    protected function parseUspItems($data): array
    {
        if (empty($data)) {
            return [];
        }

        // If already an array, return as is
        if (is_array($data)) {
            return $this->formatUspItems($data);
        }

        // Try JSON decode
        if (is_string($data)) {
            // Try JSON first
            $jsonData = $this->jsonSerializer->unserialize($data);
            if (is_array($jsonData)) {
                return $this->formatUspItems($jsonData);
            }

            // Try PHP serialize
            try {
                $serializedData = $this->serializeSerializer->unserialize($data);
                if (is_array($serializedData)) {
                    return $this->formatUspItems($serializedData);
                }
            } catch (\Exception $e) {
                // Not serialized, continue
            }
        }

        return [];
    }

    /**
     * Parse content string for menu items (looks for JSON/serialized data)
     *
     * @param string $content
     * @return array
     */
    protected function parseContentForMenuItems(string $content): array
    {
        // Look for menu_items in content (could be in data attributes, JSON, etc.)
        // Try to extract JSON from content
        if (preg_match('/menu_items["\']?\s*[:=]\s*(\[[^\]]+\]|\{[^}]+\})/i', $content, $matches)) {
            $jsonData = $this->jsonSerializer->unserialize($matches[1]);
            if (is_array($jsonData)) {
                return $this->formatMenuItems($jsonData);
            }
        }

        return [];
    }

    /**
     * Parse content string for USP items (looks for JSON/serialized data)
     *
     * @param string $content
     * @return array
     */
    protected function parseContentForUspItems(string $content): array
    {
        // Look for usp_items in content (could be in data attributes, JSON, etc.)
        // Try to extract JSON from content
        if (preg_match('/usp_items["\']?\s*[:=]\s*(\[[^\]]+\]|\{[^}]+\})/i', $content, $matches)) {
            $jsonData = $this->jsonSerializer->unserialize($matches[1]);
            if (is_array($jsonData)) {
                return $this->formatUspItems($jsonData);
            }
        }

        return [];
    }

    /**
     * Format menu items array to ensure proper structure
     *
     * @param array $items
     * @return array
     */
    protected function formatMenuItems(array $items): array
    {
        $formatted = [];
        
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            
            $formattedItem = [
                'label' => $item['label'] ?? $item['title'] ?? '',
                'url' => $item['url'] ?? $item['link'] ?? '',
                'target' => $item['target'] ?? '_self',
                'children' => []
            ];
            
            // Handle nested children
            if (isset($item['children']) && is_array($item['children'])) {
                $formattedItem['children'] = $this->formatMenuItems($item['children']);
            }
            
            $formatted[] = $formattedItem;
        }
        
        return $formatted;
    }

    /**
     * Format USP items array to ensure proper structure
     *
     * @param array $items
     * @return array
     */
    protected function formatUspItems(array $items): array
    {
        $formatted = [];
        
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            
            $formattedItem = [
                'label' => $item['label'] ?? $item['title'] ?? '',
                'icon' => $item['icon'] ?? '',
                'description' => $item['description'] ?? $item['text'] ?? ''
            ];
            
            $formatted[] = $formattedItem;
        }
        
        return $formatted;
    }

    /**
     * Extract link/url from block attributes
     *
     * @param BlockInterface $block
     * @return string
     */
    public function getLink(BlockInterface $block): string
    {
        $attributes = $block->getData();
        return $attributes['link'] ?? $attributes['url'] ?? '';
    }

    /**
     * Extract URL from block attributes
     *
     * @param BlockInterface $block
     * @return string
     */
    public function getUrl(BlockInterface $block): string
    {
        $attributes = $block->getData();
        return $attributes['url'] ?? $attributes['link'] ?? '';
    }
}
