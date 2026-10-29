<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 */

declare(strict_types=1);

namespace Innosend\Integration\Tests\Unit\Model;

use Innosend\Integration\Model\Config;
use Innosend\Integration\Model\Config\Source\Mode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Config model
 */
class ConfigTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private $scopeConfig;

    /** @var EncryptorInterface&MockObject */
    private $encryptor;

    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor   = $this->createMock(EncryptorInterface::class);
        $this->config      = new Config($this->scopeConfig, $this->encryptor);
    }

    // -----------------------------------------------------------------------
    // isEnabled
    // -----------------------------------------------------------------------

    public function testIsEnabledReturnsTrueWhenConfigSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_ENABLED, 'store', null)
            ->willReturn('1');

        $this->assertTrue($this->config->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenConfigNotSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('0');

        $this->assertFalse($this->config->isEnabled());
    }

    // -----------------------------------------------------------------------
    // getMode
    // -----------------------------------------------------------------------

    public function testGetModeReturnsConfiguredMode(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_MODE, 'store', null)
            ->willReturn(Mode::MODE_LIVE);

        $this->assertSame(Mode::MODE_LIVE, $this->config->getMode());
    }

    public function testGetModeFallsBackToTestWhenEmpty(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame(Mode::MODE_TEST, $this->config->getMode());
    }

    // -----------------------------------------------------------------------
    // getApiToken
    // -----------------------------------------------------------------------

    public function testGetApiTokenDecryptsValue(): void
    {
        $encrypted = 'encrypted_token';
        $plain     = 'my-api-token';

        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_API_TOKEN, 'store', null)
            ->willReturn($encrypted);

        $this->encryptor->method('decrypt')
            ->with($encrypted)
            ->willReturn($plain);

        $this->assertSame($plain, $this->config->getApiToken());
    }

    public function testGetApiTokenReturnsEmptyStringWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame('', $this->config->getApiToken());
    }

    public function testGetApiTokenTrimsWhitespace(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('  token  ');
        $this->encryptor->method('decrypt')->willReturn('  token  ');

        $this->assertSame('token', $this->config->getApiToken());
    }

    // -----------------------------------------------------------------------
    // getEndpointUrl
    // -----------------------------------------------------------------------

    public function testGetEndpointUrlReturnsLiveUrlInLiveMode(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'https://api.innosend.eu'],
            ]);

        $this->assertSame('https://api.innosend.eu', $this->config->getEndpointUrl());
    }

    public function testGetEndpointUrlReturnsTestUrlInTestMode(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_TEST],
                [Config::XML_PATH_TEST_ENDPOINT, 'store', null, 'https://api-staging.innosend.eu'],
            ]);

        $this->assertSame('https://api-staging.innosend.eu', $this->config->getEndpointUrl());
    }

    public function testGetEndpointUrlDefaultsToProductionWhenEmpty(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, ''],
            ]);

        $this->assertSame('https://api.innosend.eu', $this->config->getEndpointUrl());
    }

    public function testGetEndpointUrlUpgradesHttpToHttps(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'http://api.innosend.eu'],
            ]);

        $this->assertSame('https://api.innosend.eu', $this->config->getEndpointUrl());
    }

    public function testGetEndpointUrlAddsMissingScheme(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'api.innosend.eu'],
            ]);

        $this->assertSame('https://api.innosend.eu', $this->config->getEndpointUrl());
    }

    public function testGetEndpointUrlStripsTrailingSlash(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'https://api.innosend.eu/'],
            ]);

        $this->assertSame('https://api.innosend.eu', $this->config->getEndpointUrl());
    }

    // -----------------------------------------------------------------------
    // getOrganizationPath
    // -----------------------------------------------------------------------

    public function testGetOrganizationPathReturnsPathWhenIdSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_ORGANIZATION_ID, 'store', null)
            ->willReturn('42');

        $this->assertSame('organizations/42', $this->config->getOrganizationPath());
    }

    public function testGetOrganizationPathReturnsEmptyWhenNoId(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame('', $this->config->getOrganizationPath());
    }

    // -----------------------------------------------------------------------
    // isValid
    // -----------------------------------------------------------------------

    public function testIsValidReturnsTrueWhenEnabledAndTokenPresent(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_ENABLED, 'store', null, '1'],
                [Config::XML_PATH_API_TOKEN, 'store', null, 'enc_tok'],
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'https://api.innosend.eu'],
            ]);

        $this->encryptor->method('decrypt')->willReturn('my-token');

        $this->assertTrue($this->config->isValid());
    }

    public function testIsValidReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_ENABLED, 'store', null, '0'],
            ]);

        $this->assertFalse($this->config->isValid());
    }

    public function testIsValidReturnsFalseWhenTokenMissing(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::XML_PATH_ENABLED, 'store', null, '1'],
                [Config::XML_PATH_API_TOKEN, 'store', null, ''],
                [Config::XML_PATH_MODE, 'store', null, Mode::MODE_LIVE],
                [Config::XML_PATH_LIVE_ENDPOINT, 'store', null, 'https://api.innosend.eu'],
            ]);

        $this->assertFalse($this->config->isValid());
    }

    // -----------------------------------------------------------------------
    // Removed methods must not exist
    // -----------------------------------------------------------------------

    public function testGetApiKeyMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->config, 'getApiKey'));
    }

    public function testGetApiSecretMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->config, 'getApiSecret'));
    }

    public function testIsMutationAllowedMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->config, 'isMutationAllowed'));
    }

    public function testGetPickupPointsApiTokenMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->config, 'getPickupPointsApiToken'));
    }

    public function testIsPickupPointsValidMethodDoesNotExist(): void
    {
        $this->assertFalse(method_exists($this->config, 'isPickupPointsValid'));
    }
}
