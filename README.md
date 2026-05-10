# AvraAPI Laravel SDK

Official Laravel integration package for the [AvraAPI (APIX)](https://avraapi.com) enterprise API gateway.

Provides a Service Provider, Facade, and config file for seamless Laravel 10/11/12 integration. Built on top of `avraapi/php-sdk`.

> **Full documentation & guides:** [https://avraapi.com/developers/sdks](https://avraapi.com/developers/sdks)

```bash
composer require avraapi/laravel-sdk
```

## Quick Start

The package auto-discovers via `extra.laravel` in `composer.json` — no manual registration needed.

### Publish Config

```bash
php artisan vendor:publish --tag=avraapi-config
```

### Set Credentials

Add to your `.env`:

```env
AVRAAPI_API_KEY=your-api-key
AVRAAPI_API_SECRET=your-api-secret
AVRAAPI_ENV=dev
```

### Use the Facade

```php
use Avraapi\Laravel\Facades\AvraAPI;

// IP geolocation
$geo = AvraAPI::location()->lookupIp('112.134.205.126');
echo $geo->data['country']; // 'Sri Lanka'

// SMS
AvraAPI::sms()->sendSingle('0771234567', 'Hello from AvraAPI!');

// QR code
AvraAPI::utilities()->generateQr('https://avraapi.com')->saveAs('/tmp/qr.png');
```

## Services

| Service | Accessor | Endpoints |
|---------|----------|-----------|
| Location | `AvraAPI::location()` | IP geolocation lookups |
| SMS | `AvraAPI::sms()` | Single, bulk-same, bulk-different, balance |
| Utilities | `AvraAPI::utilities()` | QR codes, barcodes, **PDF generation** |
| Security | `AvraAPI::security()` | VPN & Proxy Shield, Burner Email Detection |
| Currency | `AvraAPI::currency()` | Currency codes, live rates, pair rates, conversion |

---

## PDF Generation

### Basic HTML to PDF

```php
$html = '<h1>Invoice #001</h1><p>Total: $99.00</p>';
$response = AvraAPI::utilities()->generatePdf($html);
$response->saveAs(storage_path('app/invoices/invoice.pdf'));
```

### Landscape with Custom Margins

```php
$response = AvraAPI::utilities()->generatePdf(
    html:        $html,
    pageSize:    'A4',
    orientation: 'landscape',
    margins:     ['top' => 20, 'right' => 25, 'bottom' => 20, 'left' => 25],
);
$response->saveAs(storage_path('app/reports/landscape.pdf'));
```

### Base64 JSON Response

```php
$response = AvraAPI::utilities()->generatePdf($html, responseType: 'base64');
$base64Pdf = $response->data['data'];
file_put_contents(storage_path('app/invoice.pdf'), base64_decode($base64Pdf));
```

---

## Generating PDFs from Complex Templates (Base64 Mode)

When your HTML contains quotes, newlines, inline CSS, or special characters, JSON escaping can cause issues. **Base64 mode** solves this by encoding the HTML before transport.

### Option A: Use the `generatePdfFromBase64()` Helper (Recommended)

The helper accepts **raw HTML** and encodes it automatically:

```php
// Load a complex Blade-rendered template
$html = view('invoices.pdf-template', [
    'invoice' => $invoice,
    'items'   => $items,
])->render();

// The SDK Base64-encodes internally — no manual encoding needed
$response = AvraAPI::utilities()->generatePdfFromBase64($html);
$response->saveAs(storage_path('app/invoices/inv-' . $invoice->id . '.pdf'));

// With full options:
$response = AvraAPI::utilities()->generatePdfFromBase64(
    html:        $html,
    responseType: 'binary',
    pageSize:    'Letter',
    orientation: 'landscape',
    margins:     ['top' => 15, 'right' => 20, 'bottom' => 15, 'left' => 20],
    privacyMode: true,
);
$response->saveAs(storage_path('app/invoices/inv-' . $invoice->id . '.pdf'));
```

### Option B: Manual Base64 Encoding

If you need full control, encode the HTML yourself and set `isBase64: true`:

```php
$html = view('invoices.pdf-template', $data)->render();

$response = AvraAPI::utilities()->generatePdf(
    html:     base64_encode($html),
    isBase64: true,
);
$response->saveAs(storage_path('app/invoices/invoice.pdf'));
```

### How It Works

1. The SDK sends the Base64 string in the `html` field with `is_base64: true`.
2. The server decodes the Base64 content before validation and rendering.
3. The **512 KB size limit** applies to the **decoded** HTML, not the encoded payload.

---

## Saving Files to Disk

`BinaryResponse` has a built-in `saveAs()` that creates directories automatically:

```php
// Save with Laravel's storage_path helper:
$response = AvraAPI::utilities()->generatePdf($html);
$savedPath = $response->saveAs(storage_path('app/invoices/invoice.pdf'));
echo "Saved to: {$savedPath}";

// BinaryResponse also provides:
$response->body;         // Raw binary string
$response->contentType;  // 'application/pdf'
$response->size;         // Size in bytes
$response->isPdf();      // true
$response->toDataUri();  // 'data:application/pdf;base64,...'

// Stream in a controller response:
return response($response->body, 200, [
    'Content-Type'        => $response->contentType,
    'Content-Disposition' => 'attachment; filename="invoice.pdf"',
]);
```

---

## VPN & Proxy Shield

Detect VPNs, proxies, Tor exit nodes, iCloud Private Relay, and hosting/datacenter IPs.

```php
use Avraapi\Laravel\Facades\AvraAPI;

$result = AvraAPI::security()->checkVpn('8.8.8.8');

echo $result->data['ip_address'];    // '8.8.8.8'
echo $result->data['is_vpn'];        // false
echo $result->data['is_proxy'];      // false
echo $result->data['is_tor'];        // false
echo $result->data['is_relay'];      // false
echo $result->data['is_hosting'];    // false
echo $result->data['country_code'];  // 'US'
echo $result->data['city'];          // 'Mountain View'
echo $result->data['network_name'];  // 'Google LLC'
echo $result->data['provider_name']; // 'vpnapi' or 'iplocate'

// Use in a middleware or controller:
$d = $result->data;
if ($d['is_vpn'] || $d['is_proxy'] || $d['is_tor']) {
    abort(403, 'VPN/Proxy access is not permitted.');
}
```

---

## Burner Email Shield

Detect temporary and disposable email addresses using a dual-list Redis lookup (7,000+ domains).

```php
$result = AvraAPI::security()->checkBurnerEmail('user@mailinator.com');

echo $result->data['email'];             // 'user@mailinator.com'
echo $result->data['domain'];            // 'mailinator.com'
echo $result->data['is_valid_syntax'];   // true
echo $result->data['is_disposable'];     // true
echo $result->data['source'];            // 'global', 'custom', or 'none'
echo $result->data['execution_time_ms']; // 0.42

// Guard a registration form in a controller:
if ($result->data['is_disposable']) {
    return back()->withErrors(['email' => 'Disposable emails are not allowed.']);
}
```

---

## Multi-Currency Rates & Conversion

Free currency exchange rate API — 160+ currencies, 2-hour cached rates, zero credit cost.

### Get All Currency Codes

```php
$result = AvraAPI::currency()->getCodes();

echo $result->data['count']; // 161
foreach ($result->data['codes'] as $c) {
    echo "{$c['code']} — {$c['name']}\n"; // 'USD — United States Dollar'
}
```

### Get Latest Rates from a Base Currency

```php
$result = AvraAPI::currency()->getLatestRates('USD');

echo $result->data['base'];              // 'USD'
echo $result->data['last_updated'];      // '2025-05-10T...'
echo $result->data['rates']['EUR'];      // 0.89123456
echo $result->data['rates']['LKR'];      // 298.50000000
```

### Get Pair Rate

```php
$result = AvraAPI::currency()->getPairRate('USD', 'EUR');

echo $result->data['rate'];         // 0.89123456
echo $result->data['last_updated']; // '2025-05-10T...'
```

### Convert an Amount

```php
$result = AvraAPI::currency()->convert('USD', 'LKR', 100.00);

$d = $result->data;
echo "{$d['amount']} {$d['base']} = {$d['conversion_result']} {$d['target']}";
// "100 USD = 29850.000000 LKR"
```

---

## Privacy Mode

For sensitive documents (invoices, contracts, PII), enable privacy mode to exclude HTML content from observability logs:

```php
$response = AvraAPI::utilities()->generatePdf($html, privacyMode: true);
$response->saveAs(storage_path('app/confidential/report.pdf'));
```

---

## Error Handling

All SDK exceptions propagate through the Facade and can be caught in controllers or registered in Laravel's exception handler:

```php
use Avraapi\Apix\Exceptions\ApixAuthenticationException;
use Avraapi\Apix\Exceptions\ApixInsufficientFundsException;
use Avraapi\Apix\Exceptions\ApixRateLimitException;
use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Apix\Exceptions\ApixException;

try {
    $response = AvraAPI::utilities()->generatePdf($html);
    $response->saveAs(storage_path('app/invoice.pdf'));
} catch (ApixRateLimitException $e) {
    // HTTP 429 — retry later
    return back()->with('error', 'Rate limited. Please try again shortly.');
} catch (ApixInsufficientFundsException $e) {
    // HTTP 402 — top up wallet
    return back()->with('error', 'Insufficient balance.');
} catch (ApixValidationException $e) {
    // HTTP 422 — bad input
    return back()->withErrors($e->getValidationErrors());
} catch (ApixException $e) {
    // Catch-all
    report($e);
    return back()->with('error', 'PDF generation failed.');
}
```

### Registering in Laravel's Exception Handler

```php
// bootstrap/app.php (Laravel 11+) or app/Exceptions/Handler.php

use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Apix\Exceptions\ApixException;

$exceptions->render(function (ApixValidationException $e) {
    return response()->json([
        'error'   => $e->getErrorCode(),
        'message' => $e->getMessage(),
        'fields'  => $e->getValidationErrors(),
    ], 422);
});

$exceptions->render(function (ApixException $e) {
    return response()->json([
        'error'      => $e->getErrorCode(),
        'message'    => $e->getMessage(),
        'request_id' => $e->getRequestId(),
    ], $e->getHttpStatus() ?: 500);
});
```

---

## Documentation

For full API reference, usage guides, and interactive examples, visit:

**[https://avraapi.com/developers/sdks](https://avraapi.com/developers/sdks)**

---

## License

MIT — [Fidex Developers (Pvt) Ltd](https://avraapi.com)
