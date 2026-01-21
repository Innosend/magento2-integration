# Innosend Integration Module - Technische Gids

## Architectuur

De Innosend Integration module biedt:

- API client interface en implementatie
- Configuratiebeheer
- Healthcheck endpoint
- Foutafhandeling en logging

## API Client

### ClientInterface

De `Innosend\Integration\Api\ClientInterface` biedt methoden voor API-communicatie:

- `get(string $endpoint, array $params = []): array`
- `post(string $endpoint, array $data = []): array`
- `put(string $endpoint, array $data = []): array`
- `delete(string $endpoint): array`
- `isEnabled(): bool`

### Gebruiksvoorbeeld

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

## Configuratie

Configuratie wordt beheerd via `Innosend\Integration\Model\Config`:

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
            // Configuratie is ongeldig
        }

        $apiKey = $this->config->getApiKey();
        $endpoint = $this->config->getEndpointUrl();
    }
}
```

## Foutafhandeling

Alle API-fouten worden gelogd naar:
- `var/log/system.log` - Algemene fouten
- `var/log/exception.log` - Uitzonderingen

Fouten bevatten:
- Request details (methode, URL, parameters)
- Response status en body
- Exception berichten

## Uitbreidingspunten

### Aangepaste API Client

Om een aangepaste API client implementatie te gebruiken:

```xml
<!-- etc/di.xml -->
<preference for="Innosend\Integration\Api\ClientInterface" type="Your\Module\Model\Api\CustomClient"/>
```

## Testen

### Healthcheck Test

```bash
curl http://your-store.com/innosend/api/ping
```

Verwachte response:
```json
{
    "status": "success",
    "message": "API connection successful"
}
```

## Vereisten

- Magento 2.4.x
- PHP 7.3 - 8.3
- cURL extensie

## Afhankelijkheden

- Magento_Store
- Magento_Config



















