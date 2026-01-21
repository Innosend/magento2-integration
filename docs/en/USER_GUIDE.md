# Innosend Integration Module - User Guide

## Overview

The Innosend Integration module provides the core API connection functionality for all Innosend Magento 2 modules. This module must be installed before any other Innosend modules.

## Installation

### Via Composer

```bash
composer require innosend/magento2-integration
php bin/magento module:enable Innosend_Integration
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### Manual Installation

1. Copy the module files to `app/code/Innosend/Integration/`
2. Run `php bin/magento module:enable Innosend_Integration`
3. Run `php bin/magento setup:upgrade`
4. Run `php bin/magento cache:flush`

## Configuration

1. Navigate to **Stores > Configuration > Innosend > API Configuration**
2. **Enable** the module
3. Select **Mode**: Test or Live
4. Enter your **API Key** (this will be encrypted)
5. Configure **Endpoint URLs** if different from defaults:
   - Test Endpoint: `https://api-test.innosend.com`
   - Live Endpoint: `https://api.innosend.com`
6. Set **Request Timeout** (default: 30 seconds)
7. Click **Save Config**

## Healthcheck

The module provides a healthcheck endpoint to verify API connectivity:

- **URL**: `/innosend/api/ping`
- **Method**: GET
- **Response**: JSON with status and message

Example response:
```json
{
    "status": "success",
    "message": "API connection successful"
}
```

## Troubleshooting

### API Connection Issues

- Verify API key is correct
- Check endpoint URLs are accessible
- Ensure firewall allows outbound connections
- Check Magento logs: `var/log/system.log` and `var/log/exception.log`

### Configuration Not Saving

- Clear Magento cache: `php bin/magento cache:flush`
- Check file permissions
- Verify module is enabled

## Support

For technical support, please refer to the Technical Guide or contact support@innosend.com
