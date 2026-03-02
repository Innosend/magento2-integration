<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Tests\Unit\Model\Api;

use Innosend\Integration\Model\Api\Client;
use Innosend\Integration\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for API Client
 */
class ClientTest extends TestCase
{
    /** @var Config&MockObject */
    private $config;

    /** @var Curl&MockObject */
    private $curl;

    /** @var Json&MockObject */
    private $json;

    /** @var LoggerInterface&MockObject */
    private $logger;

    private Client $client;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->curl   = $this->createMock(Curl::class);
        $this->json   = $this->createMock(Json::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client($this->config, $this->curl, $this->json, $this->logger);
    }

    // -----------------------------------------------------------------------
    // isEnabled
    // -----------------------------------------------------------------------

    public function testIsEnabledDelegatesToConfigIsValid(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->assertTrue($this->client->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenConfigInvalid(): void
    {
        $this->config->method('isValid')->willReturn(false);
        $this->assertFalse($this->client->isEnabled());
    }

    // -----------------------------------------------------------------------
    // getLastRequestUrl
    // -----------------------------------------------------------------------

    public function testGetLastRequestUrlReturnsNullBeforeAnyRequest(): void
    {
        $this->assertNull($this->client->getLastRequestUrl());
    }

    // -----------------------------------------------------------------------
    // request throws when not configured
    // -----------------------------------------------------------------------

    public function testGetThrowsWhenApiNotConfigured(): void
    {
        $this->config->method('isValid')->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->client->get('pickup-point/');
    }

    // -----------------------------------------------------------------------
    // Bearer token is used for every request
    // -----------------------------------------------------------------------

    public function testGetSendsBearerTokenHeader(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('test-bearer-token');
        $this->config->method('getTimeout')->willReturn(30);

        // addHeader is called twice: Content-Type then Authorization
        $this->curl->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Content-Type', 'application/json'],
                ['Authorization', 'Bearer test-bearer-token']
            );

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);

        $this->json->method('unserialize')->with('[]')->willReturn([]);

        $this->client->get('pickup-point/');
    }

    // -----------------------------------------------------------------------
    // URL building
    // -----------------------------------------------------------------------

    public function testGetBuildsCorrectUrlForPickupPointEndpoint(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);
        $this->json->method('unserialize')->willReturn([]);

        $capturedUrl = null;
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->callback(function (string $url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            }));

        $this->client->get('pickup-point/');

        $this->assertSame('https://api.innosend.eu/v1/pickup-point/', $capturedUrl);
    }

    public function testGetAppendsQueryParamsToUrl(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);
        $this->json->method('unserialize')->willReturn([]);

        $capturedUrl = null;
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->callback(function (string $url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            }));

        $this->client->get('pickup-point/', ['country_code' => 'NL', 'city' => 'Amsterdam']);

        $this->assertStringContainsString('country_code=NL', $capturedUrl);
        $this->assertStringContainsString('city=Amsterdam', $capturedUrl);
    }

    public function testGetAppendsArrayParamsAsRepeatedKeys(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);
        $this->json->method('unserialize')->willReturn([]);

        $capturedUrl = null;
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->callback(function (string $url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            }));

        $this->client->get('pickup-point/', ['couriers' => ['DHL', 'PostNL']]);

        $this->assertStringContainsString('couriers=DHL', $capturedUrl);
        $this->assertStringContainsString('couriers=PostNL', $capturedUrl);
    }

    public function testOrganizationPathInjectedForNonPickupEndpoints(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('organizations/99');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);
        $this->json->method('unserialize')->willReturn([]);

        $capturedUrl = null;
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->callback(function (string $url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            }));

        $this->client->get('orders');

        $this->assertStringContainsString('organizations/99', $capturedUrl);
    }

    public function testOrganizationPathNotInjectedForPickupEndpoints(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('organizations/99');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('[]');
        $this->curl->method('getStatus')->willReturn(200);
        $this->json->method('unserialize')->willReturn([]);

        $capturedUrl = null;
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->callback(function (string $url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            }));

        $this->client->get('pickup-point/courier');

        $this->assertStringNotContainsString('organizations', $capturedUrl);
    }

    // -----------------------------------------------------------------------
    // Error handling
    // -----------------------------------------------------------------------

    public function testGetThrowsLocalizedException_On4xxResponse(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('{"error":"Token invalid"}');
        $this->curl->method('getStatus')->willReturn(401);

        // One error() call from the 4xx handler; the LocalizedException re-throw skips the catch logger
        $this->logger->expects($this->once())->method('error');
        $this->expectException(LocalizedException::class);

        $this->client->get('pickup-point/');
    }

    public function testGetReturnsEmptyArrayForEmptyResponseBody(): void
    {
        $this->config->method('isValid')->willReturn(true);
        $this->config->method('getEndpointUrl')->willReturn('https://api.innosend.eu');
        $this->config->method('getOrganizationPath')->willReturn('');
        $this->config->method('getApiToken')->willReturn('tok');
        $this->config->method('getTimeout')->willReturn(30);

        $this->curl->method('getBody')->willReturn('');
        $this->curl->method('getStatus')->willReturn(200);

        $this->logger->expects($this->once())->method('warning');

        $result = $this->client->get('pickup-point/');
        $this->assertSame([], $result);
    }

    // -----------------------------------------------------------------------
    // Removed methods must not exist on Client
    // -----------------------------------------------------------------------

    public function testIsPickupPointsEnabledMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->client, 'isPickupPointsEnabled'));
    }
}
