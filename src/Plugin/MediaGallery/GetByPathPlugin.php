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

namespace Innosend\Integration\Plugin\MediaGallery;

use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\MediaGalleryApi\Model\Asset\Command\GetByPathInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to gracefully handle missing media assets
 * 
 * This plugin catches IntegrationException and NoSuchEntityException when media assets don't exist.
 * Since the interface requires AssetInterface (not nullable), we need to throw a special exception
 * that ExtractAssetsFromContent can handle, or we need to create a dummy asset.
 * 
 * However, the best approach is to let ExtractAssetsFromContent handle it, but we'll suppress
 * the critical logging here to reduce noise.
 */
class GetByPathPlugin
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
     * Around plugin to catch exceptions for missing media assets
     * 
     * Note: We can't return null because the interface requires AssetInterface.
     * We'll catch the exception and check if it's about a missing media asset.
     * If so, we'll log it as info and re-throw it so ExtractAssetsFromContent can handle it.
     *
     * @param GetByPathInterface $subject
     * @param callable $proceed
     * @param string $path
     * @return \Magento\MediaGalleryApi\Api\Data\AssetInterface
     * @throws IntegrationException
     * @throws NoSuchEntityException
     */
    public function aroundExecute(
        GetByPathInterface $subject,
        callable $proceed,
        string $path
    ) {
        try {
            return $proceed($path);
        } catch (IntegrationException $e) {
            // Check if this is a missing media asset error
            $message = $e->getMessage();
            $previous = $e->getPrevious();
            
            if (strpos($message, 'There is no such media asset') !== false ||
                ($previous && strpos($previous->getMessage(), 'There is no such media asset') !== false)) {
                // Log as info instead of critical to reduce noise
                $this->logger->info(
                    'Media asset not found (will be skipped): ' . $path
                );
            }
            // Re-throw so ExtractAssetsFromContent can handle it
            throw $e;
        } catch (NoSuchEntityException $e) {
            // Check if this is a missing media asset error
            if (strpos($e->getMessage(), 'There is no such media asset') !== false) {
                // Log as info instead of critical to reduce noise
                $this->logger->info(
                    'Media asset not found (will be skipped): ' . $path
                );
            }
            // Re-throw so ExtractAssetsFromContent can handle it
            throw $e;
        }
    }
}

