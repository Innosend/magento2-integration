# Changelog

All notable changes to the `innosend/magento2-integration` module are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed

- API client follows same-host redirects for GET requests instead of failing on a 301, and logs a clear error when a redirect cannot be followed.
- Endpoint URLs configured with `http://` (or without scheme) for `*.innosend.eu` are upgraded to `https://`.
- Carrier lookup calls only `v1/pickup-point/courier` instead of four endpoint variants.

## [1.1.0] – 2026-03-02

### Breaking changes

- **Authentication reworked**: All API requests now use a single **Bearer token** (`Authorization: Bearer {API_TOKEN}`). Basic Auth (`api_key` + `api_secret`) has been removed entirely. A bearer token from the Innosend dashboard replaces both credentials.
- **`Config::getApiKey()` removed** – use `Config::getApiToken()`.
- **`Config::getApiSecret()` removed** – use `Config::getApiToken()`.
- **`Config::isMutationAllowed()` removed** – the write-operations safety switch no longer exists.
- **`Config::getPickupPointsApiToken()` renamed** to `Config::getApiToken()`.
- **`Config::isPickupPointsValid()` removed** – `Config::isValid()` now checks `enabled + api_token + endpoint`.
- **`ClientInterface::isPickupPointsEnabled()` removed** – `ClientInterface::isEnabled()` covers all endpoints.
- **Config path changed**: `innosend_api/configuration/pickup_points/api_token` → `innosend_api/configuration/api_token`.

### Added

- `Config::getApiToken()` – reads and decrypts the API Token from the new config path.
- `Config::isValid()` – now validates `enabled + api_token + endpoint_url` (no longer requires API Key/Secret).
- `ClientInterface::isEnabled()` – unified check for all endpoints.
- `Setup\Patch\Data\MigrateConfigPaths` – data patch that runs on `setup:upgrade` and:
  - Copies `pickup_points/api_token` → `api_token`
  - Deletes removed config rows (`api_key`, `api_secret`, `allow_mutations`, `enable_pickup_points`)
- Unit tests: `tests/Unit/Model/ConfigTest`, `tests/Unit/Model/Api/ClientTest`
- Integration test: `tests/Integration/MigrateConfigPathsTest`
- `phpunit.xml` test runner configuration

### Changed

- `Client::request()` – always sends `Authorization: Bearer {token}`; no Basic Auth fallback; re-throws `LocalizedException` without double-logging.
- `Block\Adminhtml\System\Config\TestTokenConnection` – simplified field mapping (token is now at the top-level configuration group).
- `Controller\Adminhtml\System\Config\TestTokenConnection` – reads token from new config path via `Config::getApiToken()`.
- Admin config: API Token and Test Connection button moved from the `pickup_points` sub-group to the main **API Settings** group.
- URL construction: organization path is now **never** injected for `pickup-point` endpoints.
- All documentation rewritten to reflect v1.1.0 auth model.

### Removed

- `Config::XML_PATH_API_KEY`, `XML_PATH_API_SECRET`, `XML_PATH_ALLOW_MUTATIONS`, `XML_PATH_PICKUP_POINTS_API_TOKEN` constants
- `Config::getApiKey()`, `getApiSecret()`, `isMutationAllowed()`, `getPickupPointsApiToken()`, `isPickupPointsValid()` methods
- Admin config fields: `api_key`, `api_secret`, `allow_mutations`, `enable_pickup_points`, `test_connection` button
- Admin config group: `innosend_api/configuration/pickup_points`
- `Block\Adminhtml\System\Config\ApiKeyComment`
- `Block\Adminhtml\System\Config\ApiSecretComment`
- `Block\Adminhtml\System\Config\TestConnection`
- `Controller\Adminhtml\System\Config\TestConnection`
- `view/adminhtml/templates/system/config/testconnection.phtml`
- `view/adminhtml/web/js/testconnection.js`

### Migration

Run `bin/magento setup:upgrade` — the `MigrateConfigPaths` patch migrates your existing config automatically. See `docs/falconmedia/releases/1.1.0/innosend/magento2-integration/docs/` for detailed per-change migration notes.

---

## [1.0.3] – 2025-01-21

### Added

- Separate `Config::isPickupPointsValid()` for Bearer token validation
- `Config::getPickupPointsApiToken()` reads token from `innosend_api/configuration/pickup_points/api_token`
- `Client::isPickupPointsEnabled()` delegating to `isPickupPointsValid()`
- Admin `pickup_points` sub-group with API Token field and Test Token Connection button
- `Block\Adminhtml\System\Config\ApiTokenComment` – masked token display
- `Block\Adminhtml\System\Config\TestTokenConnection`
- `Controller\Adminhtml\System\Config\TestTokenConnection` – tests Bearer auth against `/v1/pickup-point/courier`
- `allow_mutations` admin toggle as a write-operations safety switch

### Changed

- `Client::request()` – uses Bearer token for `pickup-point` endpoints; Basic Auth fallback for others
- `TestConnection` controller updated to test against `/v1/orders` instead of `/integration/webshopapi/configuration`

### Fixed

- `Client::request()` catch block no longer double-logs when re-throwing `LocalizedException`

---

## [1.0.2] – 2024-XX-XX

### Added

- Initial release with API client, config model, and healthcheck endpoint
- Basic Auth (API Key + API Secret) for the webshop API
- `TestConnection` admin button testing against `integration/webshopapi/configuration`

---

## [1.0.1] – 2024-XX-XX

### Added

- Initial beta release

---

## [1.0.0] – 2024-XX-XX

### Added

- Initial release
