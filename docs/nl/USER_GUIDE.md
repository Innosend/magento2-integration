# Innosend Integration Module - Gebruikershandleiding

## Overzicht

De Innosend Integration module biedt de kern API-verbindingsfunctionaliteit voor alle Innosend Magento 2 modules. Deze module moet worden geïnstalleerd voordat andere Innosend modules kunnen worden gebruikt.

## Installatie

### Via Composer

```bash
composer require innosend/magento2-integration
php bin/magento module:enable Innosend_Integration
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento setup:upgrade --keep-generated
php bin/magento cache:flush
```

### Handmatige Installatie

1. Kopieer de module bestanden naar `app/code/Innosend/Integration/`
2. Voer uit: `php bin/magento module:enable Innosend_Integration`
3. Voer uit: `php bin/magento setup:upgrade`
4. Voer uit: `php bin/magento cache:flush`

## Configuratie

1. Ga naar **Stores > Configuration > Innosend > API Configuration**
2. **Activeer** de module
3. Selecteer **Mode**: Test of Live
4. Voer uw **API Key** in (deze wordt versleuteld opgeslagen)
5. Configureer **Endpoint URLs** indien afwijkend van standaard:
   - Test Endpoint: `https://api-test.innosend.com`
   - Live Endpoint: `https://api.innosend.com`
6. Stel **Request Timeout** in (standaard: 30 seconden)
7. Klik op **Save Config**

## Healthcheck

De module biedt een healthcheck voor de endpoint aan om API-connectiviteit te verifiëren:

- **URL**: `/innosend/api/ping`
- **Methode**: GET
- **Response**: JSON met status en bericht

Voorbeeld response:
```json
{
    "status": "success",
    "message": "API connection successful"
}
```

## Probleemoplossing

### API Verbindingsproblemen

- Controleer of API key correct is
- Controleer of endpoint URLs bereikbaar zijn
- Zorg dat firewall uitgaande verbindingen toestaat
- Controleer Magento logs: `var/log/system.log` en `var/log/exception.log`

### Configuratie Wordt Niet Opgeslagen

- Wis Magento cache:
```bash
php bin/magento cache:flush && php bin/magento cache:clean
````
- Controleer de bestandsrechten op de server
- Verifieer dat module is ingeschakeld
```bash
php bin/magento module:status Innosend_Integration
```
Als response is:
```
Innosend_Integration :  Module is disabled
```
Voer dan uit:
```bash
php bin/magento module:en Innosend_Integration
```
response moet dan zijn:
```
The following modules have been enabled:
- Innosend_Integration
```

## Support

Voor technische ondersteuning, raadpleeg de [TECHNICAL_GUIDE.md](TECHNICAL_GUIDE.md) of neem contact op met support@innosend.com
