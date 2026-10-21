# PayU S2S Store

A demo e-commerce website with **PayU Server-to-Server (S2S)** integration in PHP and an **Element Plus** checkout UI.

Supports all three payment modes:

| Mode | PayU Flow | Parameters |
|------|-----------|------------|
| **Card** | Classic S2S | `pg=CC`, `txn_s2s_flow=4`, card details |
| **UPI Collect** | S2S VPA collect | `pg=UPI`, `bankcode=UPI`, `vpa` |
| **UPI Intent** | S2S QR / deeplink | `pg=UPI`, `bankcode=INTENT` |
| **Net Banking** | S2S NB | `pg=NB`, `bankcode` (e.g. `TESTPGNB`) |

## Prerequisites

- PHP 8.0+ with `curl` extension
- PayU **test** merchant key & salt ([docs](https://docs.payu.in/docs/access-test-merchant-key-and-salt))

## Setup

1. Copy config and add your credentials:

```bash
cp config.example.php config.php
# Edit config.php — set merchant_key, merchant_salt, base_url
```

2. Start the PHP server:

```bash
cd payu-s2s-store
php -S localhost:8080 router.php
```

3. Open [http://localhost:8080](http://localhost:8080)

## Project Structure

```
payu-s2s-store/
├── public/index.html      # Element Plus storefront + checkout
├── api/
│   ├── initiate-payment.php   # S2S payment for card/UPI/NB
│   ├── validate-vpa.php       # UPI VPA validation
│   ├── verify-payment.php     # verify_payment API
│   └── banks.php              # Net banking bank list
├── lib/
│   ├── PayUHash.php           # SHA-512 hash helpers
│   └── PayUClient.php           # PayU API client
├── callback.php               # surl / furl / notifyurl handler
├── config.php
└── router.php                 # PHP built-in server router
```

## Test Credentials (Sandbox)

- **Card:** `5123456789012346` | CVV `123` | Exp `05/2030`
- **UPI VPA:** `anything@payu`
- **Net Banking:** `TESTPGNB` (test bank code)

## API Endpoints

### POST `/api/initiate-payment.php`

```json
{
  "payment_mode": "card|upi_collect|upi_intent|netbanking",
  "amount": "10.00",
  "productinfo": "Wireless Earbuds",
  "customer": { "firstname": "Test", "email": "test@example.com", "phone": "9876543210" },
  "card": { "number": "5123456789012346", "cvv": "123", "exp_month": "05", "exp_year": "2030", "name": "Test User", "bankcode": "CC" },
  "vpa": "anything@payu",
  "bankcode": "TESTPGNB"
}
```

### POST `/api/validate-vpa.php`

```json
{ "vpa": "anything@payu" }
```

### POST `/api/verify-payment.php`

```json
{ "txnid": "TXN..." }
```

## Production Checklist

- [ ] Replace test key/salt with production credentials
- [ ] Set `environment` to `production` in `config.php`
- [ ] Use HTTPS for `base_url`, `surl`, `furl`, `notifyurl`
- [ ] Verify callback hash on every response ([reverse hash](https://docs.payu.in/docs/generate-hash-merchant-hosted))
- [ ] Call `verify_payment` API server-side before fulfilling orders
- [ ] Configure PayU webhooks in dashboard
- [ ] PCI-DSS compliance required for card S2S

## References

- [Server-to-Server Integration](https://docs.payu.in/docs/server-to-server-integration)
- [Classic Integration for Cards](https://docs.payu.in/docs/integrate-with-s2s-for-cards-classic-integration)
- [UPI Collection S2S](https://docs.payu.in/docs/upi-collection-s2s)
- [UPI Intent S2S](https://docs.payu.in/docs/upi-intent-server-to-server)
- [Net Banking Seamless](https://docs.payu.in/docs/collect-payments-with-net-banking-seamless)
