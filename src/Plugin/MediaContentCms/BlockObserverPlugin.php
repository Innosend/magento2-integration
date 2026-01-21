<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-12-01
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Plugin\MediaContentCms;

use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

/**
 * Plugin to gracefully handle exceptions in CMS block media content observer
 * This prevents errors when sample data references images that don't exist
 */
class BlockObserverPlugin
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
     * Around plugin to catch exceptions when processing CMS block media content
     *
     * @param \Magento\MediaContentCms\Observer\Block $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return void
     */
    public function aroundExecute(
        \Magento\MediaContentCms\Observer\Block $subject,
        callable $proceed,
        Observer $observer
    ) {
        try {
            $proceed($observer);
        } catch (\Exception $e) {
            // Check if this is a missing media asset error
            $message = $e->getMessage();
            $previous = $e->getPrevious();
            
            if (strpos($message, 'There is no such media asset') !== false ||
                strpos($message, 'get media asset list') !== false ||
                ($previous && (strpos($previous->getMessage(), 'There is no such media asset') !== false))) {
                // Log as info instead of letting it fail
                $this->logger->info(
                    'Missing media asset in CMS block, skipping: ' . $message
                );
                // Don't re-throw, allow the CMS block to be saved
                return;
            }
            // Re-throw if it's a different exception
            throw $e;
        }
    }
}

















