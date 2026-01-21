<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-30
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Plugin\MediaContent;

use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\MediaContentApi\Api\ExtractAssetsFromContentInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to gracefully handle missing media assets during content extraction
 * This prevents errors when sample data references images that don't exist in the media gallery
 */
class ExtractAssetsFromContentPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Around plugin to catch exceptions when media assets don't exist
     * 
     * This plugin ensures that missing media assets don't break CMS block saving
     * or sample data installation. It catches exceptions and returns an empty array
     * to allow processing to continue.
     *
     * @param ExtractAssetsFromContentInterface $subject
     * @param callable $proceed
     * @param string $content
     * @return AssetInterface[]
     */
    public function aroundExecute(
        ExtractAssetsFromContentInterface $subject,
        callable $proceed,
        string $content
    ): array {
        try {
            return $proceed($content);
        } catch (\Exception $e) {
            // Check if this is a missing media asset error
            $message = $e->getMessage();
            if (strpos($message, 'There is no such media asset') !== false ||
                ($e->getPrevious() && strpos($e->getPrevious()->getMessage(), 'There is no such media asset') !== false)) {
                // Log as info instead of warning to reduce noise
                $this->logger->info(
                    'Missing media asset referenced in content, skipping: ' . $message
                );
                // Return empty array to continue processing without the missing asset
                // This allows CMS blocks and sample data to be saved even if referenced images don't exist
                return [];
            }
            // Re-throw if it's a different exception
            throw $e;
        }
    }
}

