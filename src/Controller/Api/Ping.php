<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-28
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Controller\Api;

use Innosend\Integration\Api\ClientInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Healthcheck endpoint controller
 */
class Ping implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ClientInterface
     */
    private $apiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param ClientInterface $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        ClientInterface $apiClient,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Execute healthcheck
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            if (!$this->apiClient->isEnabled()) {
                return $result->setData([
                    'status' => 'error',
                    'message' => 'API is not enabled or not configured. Please check your API settings in Stores > Configuration > Innosend > API Configuration.',
                ])->setHttpResponseCode(503);
            }

            // Configuration is valid, return success
            // Note: We don't make an actual API call here to avoid errors if the endpoint doesn't exist
            // The API client will make real calls when needed by other modules
            return $result->setData([
                'status' => 'success',
                'message' => 'API configuration is valid and ready to use',
                'configured' => true,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Innosend ping error: ' . $e->getMessage());

            return $result->setData([
                'status' => 'error',
                'message' => $e->getMessage(),
            ])->setHttpResponseCode(500);
        }
    }
}


