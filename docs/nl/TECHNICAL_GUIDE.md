# Innosend Integration – Technische Gids

## Architectuur

De module biedt:

- `ClientInterface` – HTTP-abstractie voor alle Innosend API-endpoints
- `Config` – leest en ontcijfert admin-configuratiewaarden
- `MigrateConfigPaths` – datapatch die v1.0.x-configuratiepaden migreert naar v1.1.0
- Healthcheck-controller (`/innosend/api/ping`)
- Admin-blok voor "Test API Token Connection"

## Authenticatie

Alle API-verzoeken gebruiken **Bearer-tokenauthenticatie**:

```
Authorization: Bearer {API_TOKEN}
```

Het token wordt geconfigureerd via **Stores → Configuration → Innosend → API Configuration → API Token** en versleuteld opgeslagen in `core_config_data` onder het pad `innosend_api/configuration/api_token`.

> **v1.0.x opmerking**: De vorige `api_key` + `api_secret` Basic Auth-methode en de `allow_mutations`-schakelaar zijn verwijderd. Het enkele `api_token` dekt alle endpoints.

## Config-model (`Innosend\Integration\Model\Config`)

| Methode | Type | Omschrijving |
|---|---|---|
| `isEnabled()` | `bool` | Hoofdschakelaar |
| `getMode()` | `string` | `'test'` of `'live'` |
| `getApiToken()` | `string` | Ontcijferd Bearer-token |
| `getEndpointUrl()` | `string` | Basis-URL voor de actieve mode |
| `getOrganizationPath()` | `string` | `organizations/{id}` of `''` |
| `getTimeout()` | `int` | Time-out in seconden |
| `isValid()` | `bool` | `enabled && token aanwezig && endpoint aanwezig` |

### Configuratiepad-constanten

| Constante | XML-pad |
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

De concrete implementatie (`Model\Api\Client`) doet automatisch:

1. Prefix endpoints met `/v1/`
2. Injecteert het organisatiepad voor niet-pickup-point-endpoints
3. Voegt `Authorization: Bearer {token}` toe aan elk verzoek
4. Decodeert de JSON-response en retourneert een PHP-array

### URL-opbouw

Verzoek naar `pickup-point/` met basis-URL `https://api.innosend.eu`:

```
GET https://api.innosend.eu/v1/pickup-point/
Authorization: Bearer {token}
```

Verzoek naar `orders` met organisatie-ID `42`:

```
GET https://api.innosend.eu/v1/organizations/42/orders
Authorization: Bearer {token}
```

> Het organisatiepad wordt **nooit** toegevoegd voor `pickup-point`-endpoints.

## Gebruiksvoorbeeld

```php
use Innosend\Integration\Api\ClientInterface;

class MyService
{
    public function __construct(private readonly ClientInterface $apiClient) {}

    public function haalAfhaalpuntenOp(array $params): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new \RuntimeException('Innosend API niet geconfigureerd.');
        }

        return $this->apiClient->get('pickup-point/', $params);
    }
}
```

## Dependency injection

`ClientInterface` is gebonden aan `Model\Api\Client` via `etc/di.xml`:

```xml
<preference for="Innosend\Integration\Api\ClientInterface"
            type="Innosend\Integration\Model\Api\Client"/>
```

Om de implementatie te vervangen in een eigen module:

```xml
<preference for="Innosend\Integration\Api\ClientInterface"
            type="Your\Module\Model\Api\CustomClient"/>
```

## Datapatch – configuratiemigratie

`Setup\Patch\Data\MigrateConfigPaths` wordt uitgevoerd bij `bin/magento setup:upgrade` en:

- Kopieert `innosend_api/configuration/pickup_points/api_token` → `innosend_api/configuration/api_token`
- Verwijdert verouderde paden: `api_key`, `api_secret`, `allow_mutations`, `enable_pickup_points`

## Foutafhandeling

Alle API-fouten worden gevangen en opnieuw gegooid als `LocalizedException`. Niet-`LocalizedException`-throwables worden gelogd in `var/log/system.log` voordat ze worden verpakt:

```
Innosend API Exception – {method} {url}: {message}
Innosend API Error     – status {code}: {response body}
```

## Unit tests

Voer de testsuite uit vanuit de Magento-root:

```bash
vendor/bin/phpunit package-source/innosend/magento2-integration/tests/Unit
vendor/bin/phpunit package-source/innosend/magento2-integration/tests/Integration
```

## Vereisten

- Magento 2.4.x
- PHP 8.1 – 8.3
- cURL extensie
- `Magento_Store`, `Magento_Config`
