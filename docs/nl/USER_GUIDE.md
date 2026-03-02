# Innosend Integration – Gebruikershandleiding

## Overzicht

De Innosend Integration module is de **kernafhankelijkheid** voor alle Innosend Magento 2 modules. Hij biedt de gedeelde API-client, configuratie en healthcheck-endpoint. Installeer deze module als eerste.

## Vereisten

- Magento 2.4.x
- PHP 8.1 – 8.3
- cURL extensie

## Installatie

```bash
composer require innosend/magento2-integration
php bin/magento module:enable Innosend_Integration
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Configuratie

Ga naar **Stores → Configuration → Innosend → API Configuration**.

| Veld | Omschrijving |
|---|---|
| **Enable API connection** | Hoofdschakelaar voor alle Innosend modules |
| **Mode** | `Test` (`api-staging.innosend.eu`) of `Production` (`api.innosend.eu`) |
| **Production Endpoint URL** | Standaard `https://api.innosend.eu` |
| **Test Endpoint URL** | Standaard `https://api-staging.innosend.eu` |
| **API Token** | Bearer-token uit het Innosend dashboard. Wordt door alle modules gebruikt. |
| **Test API Token Connection** | Controleert het token via `/v1/pickup-point/courier` |
| **Organization ID** | Optioneel. Alleen nodig als endpoints een organisatiepad bevatten. |
| **Request Timeout** | Seconden voordat een verzoek afloopt (standaard: 30) |

### Een API Token aanmaken

1. Log in op het [Innosend Dashboard](https://dashboard.innosend.eu)
2. Ga naar **Settings → API Keys**
3. Maak een nieuw API Token aan
4. Kopieer het token en plak het in het veld **API Token**
5. Klik op **Save Config**
6. Klik op **Test API Token Connection** ter verificatie

## Healthcheck

Een publiek healthcheck-endpoint bevestigt dat de module actief is:

- **URL**: `/innosend/api/ping`
- **Methode**: `GET`
- **Response**:

```json
{ "status": "success", "message": "API connection successful" }
```

## Probleemoplossing

### API Token niet geaccepteerd (401 / 403)

- Controleer op spaties voor of na het token.
- Controleer of de juiste **Mode** is geselecteerd (testtokens werken niet in productie).
- Genereer een nieuw token via het Innosend dashboard als het oude verlopen kan zijn.

### Geen afhaalpunten gevonden

- Controleer of **Enable API connection** op **Yes** staat.
- Gebruik **Test API Token Connection** om het token te valideren.
- Bekijk `var/log/system.log` en `var/log/exception.log` voor details.

### Configuratie wordt niet opgeslagen

- Voer `php bin/magento cache:flush` uit
- Controleer schrijfrechten op `var/` en `generated/`
- Bevestig dat de module actief is: `php bin/magento module:status Innosend_Integration`

## Support

Zie [SUPPORT.md](SUPPORT.md) voor contactgegevens en richtlijnen voor het melden van problemen.
