# Innosend Integration Module for Magento 2

This is the core integration module for Innosend in Magento 2. It provides the API client and configuration management used by other Innosend modules.

## Installation

```bash
composer require innosend/magento2-integration
php bin/magento module:enable Innosend_Integration
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuration

1. Navigate to **Stores > Configuration > Innosend > API Configuration**
2. Enable the module
3. Select Test or Live mode
4. Enter your API TOKEN
5. (Optional) configure endpoint URLs and timeout

## Notes

- The Magento module name is **`Innosend_Integration`**.
- Other Innosend modules should depend on this package for API connectivity.
