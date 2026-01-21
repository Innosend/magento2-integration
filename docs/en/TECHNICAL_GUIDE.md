# Innosend Integration Module - Technical Guide

## Architecture

The Innosend Integration module provides:

- API client interface and implementation
- Configuration management
- Healthcheck endpoint
- Error handling and logging

## API Client

### ClientInterface

The `Innosend\Integration\Api\ClientInterface` provides methods for API communication:

- `get(string $endpoint, array $params = []): array`
- `post(string $endpoint, array $data = []): array`
- `put(string $endpoint, array $data = []): array`
- `delete(string $endpoint): array`
- `isEnabled(): bool`

### Usage Example

```php
use Innosend\Integration\Api\ClientInterface;

class MyService
{
    private $apiClient;

    public function __construct(ClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function fetchData()
    {
        if (!$this->apiClient->isEnabled()) {
            throw new \Exception('API not enabled');
        }

        return $this->apiClient->get('endpoint', ['param' => 'value']);
    }
}
```

## Configuration

Configuration is managed via `Innosend\Integration\Model\Config`:

```php
use Innosend\Integration\Model\Config;

class MyService
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function checkConfig()
    {
        if (!$this->config->isValid()) {
            // Configuration is invalid
        }

        $apiKey = $this->config->getApiKey();
        $endpoint = $this->config->getEndpointUrl();
    }
}
```

## Error Handling

All API errors are logged to:
- `var/log/system.log` - General errors
- `var/log/exception.log` - Exceptions

Errors include:
- Request details (method, URL, parameters)
- Response status and body
- Exception messages

## Extension Points

### Custom API Client

To use a custom API client implementation:

```xml
<!-- etc/di.xml -->
<preference for="Innosend\Integration\Api\ClientInterface" type="Your\Module\Model\Api\CustomClient"/>
```

## Testing

### Healthcheck Test

```bash
curl http://your-store.com/innosend/api/ping
```

Expected response:
```json
{
    "status": "success",
    "message": "API connection successful"
}
```

## Requirements

- Magento 2.4.x
- PHP 7.3 - 8.3
- cURL extension

## Dependencies

- Magento_Store
- Magento_Config
