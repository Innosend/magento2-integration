# Innosend Integration – Support

## Contact

| Channel | Address |
|---|---|
| Support email | support@innosend.eu |
| Developer email | henk@falconmedia.nl |
| Dashboard | https://dashboard.innosend.eu |

## Before opening a ticket

1. Run **Test API Token Connection** in the Magento admin.
2. Check `var/log/system.log` and `var/log/exception.log` for recent errors.
3. Confirm the module is enabled and the API Token is set.

## Information to include

| Item | Where to find it |
|---|---|
| Magento version | `php bin/magento --version` |
| PHP version | `php -v` |
| Module version | `composer show innosend/magento2-integration` |
| Error message | `var/log/system.log` |
| Steps to reproduce | — |

## Common issues

| Symptom | Likely cause |
|---|---|
| 401 Unauthorized | Invalid or expired API Token |
| 404 Not Found | Wrong endpoint URL or mode (test vs. production) |
| Empty pickup point list | API Token valid but no carriers configured for the address/country |
| Config not saving | Cache not flushed; run `php bin/magento cache:flush` |

## Log files

- `var/log/system.log` – general API errors
- `var/log/exception.log` – uncaught exceptions
- `var/log/debug.log` – verbose output (when Magento debug logging is enabled)
