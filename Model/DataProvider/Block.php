<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\DataProvider;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Block
{
    /**
     * @param BlockRepositoryInterface $blockRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected BlockRepositoryInterface $blockRepository,
        protected StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Get ContentBlock by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getContentBlock(string $identifier): ?array
    {
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $block = $this->blockRepository->getById($identifier);
            
            if (!$block->isActive()) {
                return null;
            }

            $data = $this->prepareBlockData($block);
            
            // Get children blocks for menu_items and usp_items
            $data['menu_items'] = $this->getChildBlocks($block, 'menu');
            $data['usp_items'] = $this->getChildBlocks($block, 'usp');

            return $data;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Prepare block data with link and url fields
     *
     * @param BlockInterface $block
     * @return array
     */
    protected function prepareBlockData(BlockInterface $block): array
    {
        $data = [
            'identifier' => $block->getIdentifier(),
            'title' => $block->getTitle(),
            'content' => $block->getContent(),
            'link' => $this->extractLink($block),
            'url' => $this->extractUrl($block),
        ];

        return $data;
    }

    /**
     * Extract link from block content or custom attribute
     *
     * @param BlockInterface $block
     * @return string
     */
    protected function extractLink(BlockInterface $block): string
    {
        // Try to get link from custom attribute or content parsing
        // This is a placeholder - adjust based on your actual data structure
        $content = $block->getContent() ?? '';
        
        // Check if block has a custom link attribute (if using custom attributes)
        // For now, return empty string - implement based on your CMS block structure
        return '';
    }

    /**
     * Extract URL from block content or custom attribute
     *
     * @param BlockInterface $block
     * @return string
     */
    protected function extractUrl(BlockInterface $block): string
    {
        // Try to get URL from custom attribute or content parsing
        // This is a placeholder - adjust based on your actual data structure
        $content = $block->getContent() ?? '';
        
        // Check if block has a custom URL attribute (if using custom attributes)
        // For now, return empty string - implement based on your CMS block structure
        return '';
    }

    /**
     * Get child blocks for menu or USP items
     *
     * @param BlockInterface $parentBlock
     * @param string $type
     * @return array
     */
    protected function getChildBlocks(BlockInterface $parentBlock, string $type): array
    {
        // This method should fetch child blocks based on your ContentBlock structure
        // The implementation depends on how child blocks are stored/related
        // This is a placeholder - implement based on your actual data structure
        
        // Example: If child blocks are stored with identifiers like "mega_menu_item_1", "mega_menu_item_2"
        // or if there's a parent-child relationship in your CMS structure
        
        return [];
    }

    /**
     * Format menu items recursively
     *
     * @param array $items
     * @return array
     */
    public function formatMenuItems(array $items): array
    {
        $formatted = [];
        
        foreach ($items as $item) {
            $formattedItem = [
                'identifier' => $item['identifier'] ?? '',
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'link' => $item['link'] ?? '',
                'url' => $item['url'] ?? '',
            ];

            // Recursively format nested menu items
            if (isset($item['menu_items']) && is_array($item['menu_items'])) {
                $formattedItem['menu_items'] = $this->formatMenuItems($item['menu_items']);
            } else {
                $formattedItem['menu_items'] = [];
            }

            $formatted[] = $formattedItem;
        }

        return $formatted;
    }

    /**
     * Format USP items recursively
     *
     * @param array $items
     * @return array
     */
    public function formatUspItems(array $items): array
    {
        $formatted = [];
        
        foreach ($items as $item) {
            $formattedItem = [
                'identifier' => $item['identifier'] ?? '',
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'link' => $item['link'] ?? '',
                'url' => $item['url'] ?? '',
            ];

            // Recursively format nested USP items
            if (isset($item['usp_items']) && is_array($item['usp_items'])) {
                $formattedItem['usp_items'] = $this->formatUspItems($item['usp_items']);
            } else {
                $formattedItem['usp_items'] = [];
            }

            $formatted[] = $formattedItem;
        }

        return $formatted;
    }
}
