# Innosend Integration – Support

## Contact

| Kanaal | Adres |
|---|---|
| Support e-mail | support@innosend.eu |
| Ontwikkelaar e-mail | henk@falconmedia.nl |
| Dashboard | https://dashboard.innosend.eu |

## Voordat je een ticket opent

1. Voer **Test API Token Connection** uit in het Magento-beheer.
2. Controleer `var/log/system.log` en `var/log/exception.log` op recente fouten.
3. Bevestig dat de module ingeschakeld is en het API Token ingevuld is.

## Te vermelden informatie

| Gegeven | Waar te vinden |
|---|---|
| Magento-versie | `php bin/magento --version` |
| PHP-versie | `php -v` |
| Moduleversie | `composer show innosend/magento2-integration` |
| Foutmelding | `var/log/system.log` |
| Stappen om te reproduceren | — |

## Veelvoorkomende problemen

| Symptoom | Waarschijnlijke oorzaak |
|---|---|
| 401 Unauthorized | Ongeldig of verlopen API Token |
| 404 Not Found | Verkeerde endpoint-URL of mode (test vs. productie) |
| Lege afhaalpuntenlijst | Token geldig, maar geen carriers beschikbaar voor dit adres/land |
| Configuratie wordt niet opgeslagen | Cache niet geleegd; voer `php bin/magento cache:flush` uit |

## Logbestanden

- `var/log/system.log` – algemene API-fouten
- `var/log/exception.log` – niet-afgevangen uitzonderingen
- `var/log/debug.log` – uitgebreide uitvoer (wanneer Magento debug-logging actief is)
