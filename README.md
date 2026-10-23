# PayU S2S Store

A standalone PHP + Element UI demo store with PayU **Server-to-Server (S2S)** integration supporting:

- **Cards** (Credit & Debit) — Classic S2S with 3DS ACS redirect
- **UPI Collect** — VPA validation + collect request
- **UPI Intent** — Deep link to GPay, PhonePe, Paytm, etc.
- **Net Banking** — Bank selection with S2S redirect

Built using official PayU integration patterns from [PayU Developer Docs](https://docs.payu.in/docs/server-to-server-integration).

## Prerequisites

- PHP 8.1+ with `curl` extension
- PayU test merchant **Key** and **Salt** ([Get test credentials](https://docs.payu.in/docs/test-merchant-key-and-salt))

## Quick Start

```bash
cd payu-s2s-store

# Copy and edit credentials
cp .env.example .env

# Load env vars (optional — defaults to PayU test key/salt)
export $(grep -v '^#' .env | xargs)

# Start PHP built-in server
php -S localhost:8080 router.php
```

Open **http://localhost:8080** in your browser.

## Project Structure

```
payu-s2s-store/
├── index.php              # Element UI checkout page
├── callback.php           # surl/furl handler with hash verification
├── webhook.php            # S2S webhook receiver
├── router.php             # PHP dev server router
├── api/
│   ├── process-payment.php    # Initiate S2S payment (all paymodes)
│   ├── validate-vpa.php       # UPI VPA validation
│   ├── netbanking-banks.php   # Fetch NB bank list
│   └── verify-payment.php     # Verify payment status
├── config/config.php      # App & PayU configuration
└── lib/
    ├── PayUHash.php       # SHA-512 hash generation & verification
    ├── PayUClient.php     # PayU API client
    └── PaymentService.php # Payment mode handlers
```

## Payment Modes

| Mode | `pg` | `bankcode` | Notes |
|------|------|------------|-------|
| Credit Card | `CC` | `CC` | Requires `txn_s2s_flow=4`, ACS template in response |
| Debit Card | `DC` | `DC` | Same as CC |
| UPI Collect | `UPI` | `UPI` | Requires `vpa` parameter |
| UPI Intent | `UPI` | `INTENT` | Returns `intentURIData` deeplink |
| Net Banking | `NB` | Bank code | e.g. `TESTPGNB`, `SBIB`, `AXIB` |

## Configuration

Edit `config/config.php` or set environment variables:

| Variable | Description |
|----------|-------------|
| `PAYU_MERCHANT_KEY` | Merchant key from PayU dashboard |
| `PAYU_MERCHANT_SALT` | Merchant salt (never expose to frontend) |
| `PAYU_ENV` | `test` or `production` |
| `APP_BASE_URL` | Public URL for surl/furl callbacks |

## Test Cards (PayU Test Environment)

| Card Number | Type |
|-------------|------|
| 5123456789012346 | Mastercard |
| 4111111111111111 | Visa |

Use any future expiry and CVV `123`.

## Security Notes

- **PCI-DSS certification** is mandatory for card S2S integration in production
- Salt and hash generation must always happen server-side
- Always verify reverse hash on callback responses
- Use webhooks + Verify Payment API for final status confirmation

## API Endpoints

### POST `/api/process-payment.php`

```json
{
  "paymode": "card|upi_collect|upi_intent|netbanking",
  "amount": 10,
  "firstname": "Test",
  "email": "test@payu.in",
  "phone": "9876543210",
  "productinfo": "Order",
  "ccnum": "5123456789012346",
  "ccexpmon": "05",
  "ccexpyr": "2026",
  "ccvv": "123",
  "ccname": "Test User",
  "card_type": "cc",
  "vpa": "test@upi",
  "upi_app": "gpay",
  "bankcode": "TESTPGNB"
}
```

### POST `/api/validate-vpa.php`

```json
{ "vpa": "yourname@upi" }
```

### GET `/api/netbanking-banks.php`

Returns list of available net banking options.

### POST `/api/verify-payment.php`

```json
{ "txnid": "TXN202607141200001234" }
```

## References

- [Server-to-Server Integration](https://docs.payu.in/docs/server-to-server-integration)
- [Classic Cards S2S](https://docs.payu.in/docs/integrate-with-s2s-for-cards-classic-integration)
- [UPI Collection S2S](https://docs.payu.in/docs/upi-collection-s2s)
- [UPI Intent S2S](https://docs.payu.in/docs/upi-intent-server-to-server)
- [Net Banking Integration](https://docs.payu.in/docs/collect-payments-with-net-banking-seamless)
