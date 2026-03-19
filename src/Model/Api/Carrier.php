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

namespace Innosend\Integration\Model\Api;

use Innosend\Integration\Api\CarrierInterface;
use Innosend\Integration\Api\ClientInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Carrier API implementation
 */
class Carrier implements CarrierInterface
{
    /**
     * @var ClientInterface
     */
    private $apiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ClientInterface $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Get available carriers from Innosend
     *
     * @return array List of carriers with id, name, and code
     * @throws LocalizedException
     */
    public function getCarriers(): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new LocalizedException(__('Innosend API is not enabled.'));
        }

        try {
            // Try different endpoint variations
            // Test shows v1/pickup-point/courier (without trailing slash) works best
            $endpoints = [
                'v1/pickup-point/courier',      // Most common working endpoint
                'pickup-point/courier',          // Fallback without version
                'v1/pickup-point/courier/',     // With trailing slash
                'pickup-point/courier/',         // With trailing slash, no version
            ];

            $lastException = null;
            foreach ($endpoints as $endpoint) {
                try {
                    $this->logger->info('Trying to fetch carriers from endpoint: ' . $endpoint);
                    $response = $this->apiClient->get($endpoint);

                    $this->logger->debug('Carrier API response: ' . json_encode($response));

                    // Handle different response structures
                    // Priority 1: Check for array of strings (most common format: ["PostNL", "DHL", ...])
                    if (is_array($response) && !empty($response) && isset($response[0]) && is_string($response[0])) {
                        $this->logger->info('Found carriers as array of strings: ' . count($response));
                        // Convert strings to carrier objects
                        $carriers = [];
                        foreach ($response as $carrierName) {
                            if (is_string($carrierName) && !empty(trim($carrierName))) {
                                $carriers[] = [
                                    'code' => strtolower(trim($carrierName)),
                                    'name' => trim($carrierName),
                                    'id' => strtolower(trim($carrierName))
                                ];
                            }
                        }
                        if (!empty($carriers)) {
                            $this->logger->info('Converted ' . count($carriers) . ' string carriers to objects');
                            return $carriers;
                        }
                    }

                    // Priority 2: Check for response['data'] structure
                    if (isset($response['data']) && is_array($response['data'])) {
                        $this->logger->info('Found carriers in response[data]: ' . count($response['data']));
                        // Check if data contains strings
                        if (!empty($response['data']) && isset($response['data'][0]) && is_string($response['data'][0])) {
                            $carriers = [];
                            foreach ($response['data'] as $carrierName) {
                                if (is_string($carrierName) && !empty(trim($carrierName))) {
                                    $carriers[] = [
                                        'code' => strtolower(trim($carrierName)),
                                        'name' => trim($carrierName),
                                        'id' => strtolower(trim($carrierName))
                                    ];
                                }
                            }
                            if (!empty($carriers)) {
                                $this->logger->info('Converted ' . count($carriers) . ' string carriers from data to objects');
                                return $carriers;
                            }
                        }
                        return $response['data'];
                    }

                    // Priority 3: Direct array response (objects)
                    if (is_array($response) && !empty($response)) {

                        // Check if it's a list of carrier objects (has numeric keys with arrays)
                        if (isset($response[0]) && is_array($response[0])) {
                            $this->logger->info('Found carriers as indexed array: ' . count($response));
                            return $response;
                        }

                        // Check if it's a single carrier object
                        if (isset($response['code']) || isset($response['id']) || isset($response['name']) || isset($response['title'])) {
                            $this->logger->info('Found single carrier object');
                            return [$response];
                        }

                        // Check if response has string keys that might be carrier codes
                        $hasCarrierFields = false;
                        foreach ($response as $key => $value) {
                            if (is_array($value) && (isset($value['code']) || isset($value['id']) || isset($value['name']))) {
                                $hasCarrierFields = true;
                                break;
                            }
                        }

                        if ($hasCarrierFields) {
                            $this->logger->info('Found carriers as associative array: ' . count($response));
                            return array_values($response);
                        }
                    }

                    // If we get here, the response structure is unexpected
                    $this->logger->warning('Unexpected response structure for endpoint: ' . $endpoint);
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to fetch carriers from endpoint ' . $endpoint . ': ' . $e->getMessage());
                    $lastException = $e;
                    continue;
                }
            }

            // If all endpoints failed, throw the last exception
            if ($lastException) {
                throw $lastException;
            }

            $this->logger->warning('No carriers found in API response');
            return [];
        } catch (LocalizedException $e) {
            $this->logger->error('LocalizedException fetching carriers: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error fetching carriers from Innosend: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(
                __('Error fetching carriers from Innosend: %1', $e->getMessage())
            );
        }
    }
}
