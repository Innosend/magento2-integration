# Innosend Integration – Technical Guide

## Architecture

The module provides:

- `ClientInterface` – HTTP abstraction for all Innosend API endpoints
- `Config` – reads and decrypts admin configuration values
- `MigrateConfigPaths` – data patch that migrates v1.0.x config paths to v1.1.0
- Healthcheck controller (`/innosend/api/ping`)
- Admin block for "Test API Token Connection"

## Authentication

All API requests use **Bearer token authentication**:

```
Authorization: Bearer {API_TOKEN}
```

The token is configured at **Stores → Configuration → Innosend → API Configuration → API Token** and is stored encrypted in `core_config_data` under the path `innosend_api/configuration/api_token`.

> **v1.0.x note**: The previous `api_key` + `api_secret` Basic Auth approach and the `allow_mutations` safety switch have been removed. The single `api_token` covers all endpoints.

## Config model (`Innosend\Integration\Model\Config`)

| Method | Returns | Description |
|---|---|---|
| `isEnabled()` | `bool` | Master enabled flag |
| `getMode()` | `string` | `'test'` or `'live'` |
| `getApiToken()` | `string` | Decrypted Bearer token |
| `getEndpointUrl()` | `string` | Base URL for the active mode |
| `getOrganizationPath()` | `string` | `organizations/{id}` or `''` |
| `getTimeout()` | `int` | Request timeout in seconds |
| `isValid()` | `bool` | `enabled && token present && endpoint present` |

### Config path constants

| Constant | XML path |
|---|---|
| `XML_PATH_ENABLED` | `innosend_api/configuration/enabled` |
| `XML_PATH_MODE` | `innosend_api/configuration/mode` |
| `XML_PATH_API_TOKEN` | `innosend_api/configuration/api_token` |
| `XML_PATH_TEST_ENDPOINT` | `innosend_api/configuration/test_endpoint` |
| `XML_PATH_LIVE_ENDPOINT` | `innosend_api/configuration/live_endpoint` |
| `XML_PATH_ORGANIZATION_ID` | `innosend_api/configuration/organization_id` |
| `XML_PATH_TIMEOUT` | `innosend_api/configuration/timeout` |

## ClientInterface (`Innosend\Integration\Api\ClientInterface`)

```php
interface ClientInterface
{
    public function get(string $endpoint, array $params = []): array;
    public function post(string $endpoint, array $data = []): array;
    public function put(string $endpoint, array $data = []): array;
    public function delete(string $endpoint): array;
    public function isEnabled(): bool;
}
```

The concrete implementation (`Model\Api\Client`) automatically:

1. Prefixes endpoints with `/v1/`
2. Injects the organization path for non-pickup-point endpoints
3. Adds `Authorization: Bearer {token}` to every request
4. Decodes the JSON response and returns a PHP array

### URL construction

For a request to `pickup-point/` with base URL `https://api.innosend.eu`:

```
GET https://api.innosend.eu/v1/pickup-point/
Authorization: Bearer {token}
```

For a request to `orders` with organization ID `42`:

```
GET https://api.innosend.eu/v1/organizations/42/orders
Authorization: Bearer {token}
```

> Organization path is **never** injected for `pickup-point` endpoints.

## Usage example

```php
use Innosend\Integration\Api\ClientInterface;

class MyService
{
    public function __construct(private readonly ClientInterface $apiClient) {}

    public function fetchPickupPoints(array $params): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new \RuntimeException('Innosend API not configured.');
        }

        return $this->apiClient->get('pickup-point/', $params);
    }
}
```

## Dependency injection

The `ClientInterface` is bound to `Model\Api\Client` via `etc/di.xml`:

```xml
<preference for="Innosend\Integration\Api\ClientInterface"
            type="Innosend\Integration\Model\Api\Client"/>
```

To swap the implementation in a custom module:

```xml
<preference for="Innosend\Integration\Api\ClientInterface"
            type="Your\Module\Model\Api\CustomClient"/>
```

## Data patch – config migration

`Setup\Patch\Data\MigrateConfigPaths` runs on `bin/magento setup:upgrade` and:

- Copies `innosend_api/configuration/pickup_points/api_token` → `innosend_api/configuration/api_token`
- Deletes removed paths: `api_key`, `api_secret`, `allow_mutations`, `enable_pickup_points`

## Error handling

All errors from the API are caught and re-thrown as `LocalizedException`. Non-`LocalizedException` throwables are logged to `var/log/system.log` before being wrapped:

```
Innosend API Exception – {method} {url}: {message}
Innosend API Error     – status {code}: {response body}
```

## Unit tests

Run the module's own test suite from the Magento root:

```bash
vendor/bin/phpunit package-source/innosend/magento2-integration/tests/Unit
vendor/bin/phpunit package-source/innosend/magento2-integration/tests/Integration
```

## Requirements

- Magento 2.4.x
- PHP 8.1 – 8.3
- cURL extension
- `Magento_Store`, `Magento_Config`
