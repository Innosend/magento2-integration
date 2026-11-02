<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.1.0
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Model\Api;

use Innosend\Integration\Api\ClientInterface;
use Innosend\Integration\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * API client implementation for Innosend
 * All requests use Bearer token authentication (Authorization: Bearer {API_TOKEN}).
 */
class Client implements ClientInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $lastRequestUrl = null;

    /**
     * Maximum number of redirects followed for a GET request
     */
    private const MAX_REDIRECTS = 3;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Get last request URL (for debugging)
     *
     * @return string|null
     */
    public function getLastRequestUrl(): ?string
    {
        return $this->lastRequestUrl;
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     * @throws LocalizedException
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws LocalizedException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws LocalizedException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [], $data);
    }

    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws LocalizedException
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Check if the API is enabled and configured (Bearer token present)
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isValid();
    }

    /**
     * Make HTTP request using Bearer token authentication
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array $data Request body data
     * @return array Response data
     * @throws LocalizedException
     */
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array
    {
        if (!$this->config->isValid()) {
            throw new LocalizedException(
                __('Innosend API is not enabled or not properly configured. '
                    . 'Please set the API Token under Innosend > API Configuration.')
            );
        }

        $baseUrl = $this->config->getEndpointUrl();
        $organizationPath = $this->config->getOrganizationPath();

        $endpointPath = ltrim($endpoint, '/');
        $isPickupPointEndpoint = strpos($endpointPath, 'pickup-point') !== false;

        // Ensure /v1/ prefix
        if (strpos($endpointPath, 'v1/') !== 0) {
            $endpointPath = 'v1/' . $endpointPath;
        }

        // Organization path applies only to non-pickup-point endpoints
        if (!empty($organizationPath) && !$isPickupPointEndpoint) {
            if (strpos($endpointPath, 'v1/') === 0
                && strpos($endpointPath, 'v1/organizations/') !== 0
            ) {
                $endpointPath = 'v1/' . $organizationPath . '/' . substr($endpointPath, 3);
            } elseif (strpos($endpointPath, 'organizations/') !== 0) {
                $endpointPath = $organizationPath . '/' . $endpointPath;
            }
        }

        $url = rtrim($baseUrl, '/') . '/' . $endpointPath;

        if (!empty($params)) {
            $queryParts = [];
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $queryParts[] = urlencode($key) . '=' . urlencode((string) $item);
                    }
                } else {
                    $queryParts[] = urlencode($key) . '=' . urlencode((string) $value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }

        $this->lastRequestUrl = $url;

        try {
            $this->curl->setTimeout($this->config->getTimeout());
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $this->config->getApiToken());

            if (!empty($data)) {
                $this->curl->addHeader('Content-Length', (string) strlen($this->json->serialize($data)));
            }

            switch ($method) {
                case 'GET':
                    $this->curl->get($url);
                    break;
                case 'POST':
                    $this->curl->post($url, $this->json->serialize($data));
                    break;
                case 'PUT':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                    $this->curl->post($url, $this->json->serialize($data));
                    break;
                case 'DELETE':
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                    $this->curl->get($url);
                    break;
            }

            $statusCode = $this->curl->getStatus();

            // Follow redirects (e.g. http -> https or a trailing slash) ourselves for GET requests:
            // CURLOPT_FOLLOWLOCATION would make Magento's Curl client report the first (3xx) status
            $redirects = 0;
            while ($method === 'GET' && $statusCode >= 300 && $statusCode < 400 && $redirects < self::MAX_REDIRECTS) {
                $location = $this->getRedirectLocation($url);
                if ($location === null) {
                    break;
                }

                $this->logger->debug('Innosend API redirect', [
                    'url'      => $url,
                    'status'   => $statusCode,
                    'location' => $location,
                ]);

                $url = $location;
                $this->lastRequestUrl = $url;
                $this->curl->get($url);
                $statusCode = $this->curl->getStatus();
                $redirects++;
            }

            $responseBody = $this->curl->getBody();

            if ($statusCode >= 300 && $statusCode < 400) {
                $this->logger->error('Innosend API redirect not followed', [
                    'method'   => $method,
                    'url'      => $url,
                    'status'   => $statusCode,
                    'location' => $this->getLocationHeader(),
                ]);
                throw new LocalizedException(
                    __(
                        'Innosend API returned redirect %1 to "%2". Please check the API endpoint URL.',
                        $statusCode,
                        $this->getLocationHeader()
                    )
                );
            }

            if ($statusCode >= 400) {
                $this->logger->error('Innosend API Error', [
                    'method'   => $method,
                    'url'      => $url,
                    'status'   => $statusCode,
                    'response' => $responseBody,
                ]);
                throw new LocalizedException(__('Innosend API error: %1', $responseBody));
            }

            if (empty($responseBody)) {
                $this->logger->warning('Empty response from API', [
                    'method' => $method,
                    'url'    => $url,
                    'status' => $statusCode,
                ]);
                return [];
            }

            try {
                $decoded = $this->json->unserialize($responseBody);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                $this->logger->error('Failed to decode JSON response', [
                    'method'   => $method,
                    'url'      => $url,
                    'response' => substr($responseBody, 0, 500),
                    'error'    => $e->getMessage(),
                ]);
                throw new LocalizedException(
                    __('Invalid JSON response from Innosend API: %1', $e->getMessage())
                );
            }
        } catch (\Throwable $e) {
            if ($e instanceof LocalizedException) {
                throw $e;
            }

            $this->logger->error('Innosend API Exception', [
                'method' => $method,
                'url'    => $url,
                'error'  => $e->getMessage(),
            ]);

            throw new LocalizedException(
                __('Error communicating with Innosend API: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get the Location header of the last response
     *
     * @return string
     */
    private function getLocationHeader(): string
    {
        $headers = $this->curl->getHeaders();

        return trim((string) ($headers['Location'] ?? $headers['location'] ?? ''));
    }

    /**
     * Resolve the redirect target of the last response, only on the same host so the Bearer token stays there
     *
     * @param string $currentUrl
     * @return string|null
     */
    private function getRedirectLocation(string $currentUrl): ?string
    {
        $location = $this->getLocationHeader();
        if ($location === '') {
            return null;
        }

        $current = parse_url($currentUrl);
        if (strpos($location, '/') === 0) {
            $location = 'https://' . ($current['host'] ?? '') . $location;
        }

        $target = parse_url($location);
        if (($target['scheme'] ?? '') !== 'https'
            || strcasecmp((string) ($target['host'] ?? ''), (string) ($current['host'] ?? '')) !== 0
        ) {
            return null;
        }

        return $location;
    }
}
