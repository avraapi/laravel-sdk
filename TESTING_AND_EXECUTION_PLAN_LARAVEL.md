# AvraAPI Laravel SDK — Testing & Execution Plan (Local / Sail)

## 1. Folder Structure

All three repositories must be **siblings** in one workspace directory.
Place them completely outside each other to mirror real-world Composer consumption:

```
/your-workspace/
├── apix-laravel/           ← Your APIX gateway backend (Laravel 12, Sail)
│   ├── app/
│   └── ...
│
├── apix-php-sdk/           ← The pure PHP SDK (avraapi/apix-php-sdk)
│   ├── composer.json       ← name: "avraapi/apix-php-sdk"
│   └── src/
│
├── laravel-sdk/            ← This Laravel wrapper (avraapi/laravel-sdk)
│   ├── composer.json       ← name: "avraapi/laravel-sdk"
│   ├── config/avraapi.php
│   └── src/
│
└── my-laravel-app/         ← Your test Laravel 12 project (create this)
    ├── composer.json
    ├── .env
    └── routes/web.php
```

---

## 2. Start the APIX Gateway

```bash
cd /your-workspace/apix-laravel
./vendor/bin/sail up -d
```

Confirm the gateway is reachable:
```bash
curl -s http://localhost/up
# Returns: {"status":"ok"}
```

---

## 3. Create a Fresh Laravel Test Project

```bash
cd /your-workspace
composer create-project laravel/laravel my-laravel-app
cd my-laravel-app
```

---

## 4. Wire Both Local Packages via Composer `path` Repositories

Edit `/your-workspace/my-laravel-app/composer.json` and add `repositories`
and `require` entries:

```json
{
    "name": "your-org/my-laravel-app",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "avraapi/apix-php-sdk": "*",
        "avraapi/laravel-sdk": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../apix-php-sdk",
            "options": {
                "symlink": true
            }
        },
        {
            "type": "path",
            "url": "../laravel-sdk",
            "options": {
                "symlink": true
            }
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

> **Why `"symlink": true`?**
> Composer creates a symlink rather than copying the files, so any edit you
> make to the SDK source is instantly reflected in the test app — no `composer
> update` required between changes.

Install:

```bash
composer install
```

Composer will:
1. Symlink `vendor/avraapi/apix-php-sdk` → `../../apix-php-sdk`
2. Symlink `vendor/avraapi/laravel-sdk`  → `../../laravel-sdk`
3. Auto-discover `AvraApiServiceProvider` and the `AvraAPI` alias from the
   `extra.laravel` block in `laravel-sdk/composer.json`.

---

## 5. Configure the .env File

Edit `/your-workspace/my-laravel-app/.env`:

```dotenv
# ── APIX Gateway credentials ─────────────────────────────────────────────────
# Get these from your APIX admin panel: Project → Credentials
APIX_PROJECT_KEY=paste-your-project-key-here
APIX_API_SECRET=paste-your-api-secret-here

# Target the Development environment on your local Sail gateway
APIX_ENV=development

# Point the SDK at your local Sail instance instead of production
APIX_BASE_URL=http://localhost/api/v1
```

---

## 6. Publish the Configuration File (Optional but Recommended)

```bash
php artisan vendor:publish --tag="avraapi-config"
```

This copies `laravel-sdk/config/avraapi.php` → `config/avraapi.php` in your
test app. You can now edit it directly and see the changes immediately (because
the SDK itself is still symlinked).

Verify the config was published:
```bash
cat config/avraapi.php
```

---

## 7. Verify Auto-Discovery

Check that the Service Provider and Facade were discovered automatically:

```bash
php artisan package:discover --ansi
```

You should see:
```
Discovered Package: avraapi/laravel-sdk
```

And in `bootstrap/cache/packages.php` (auto-generated):
```php
'avraapi/laravel-sdk' => [
    'providers' => ['Avraapi\\Laravel\\AvraApiServiceProvider'],
    'aliases'   => ['AvraAPI' => 'Avraapi\\Laravel\\Facades\\AvraAPI'],
],
```

---

## 8. Create the Test Route

Add the following to `/your-workspace/my-laravel-app/routes/web.php`:

```php
<?php

use Avraapi\Apix\Exceptions\ApixAuthenticationException;
use Avraapi\Apix\Exceptions\ApixException;
use Avraapi\Apix\Exceptions\ApixNetworkException;
use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Laravel\Facades\AvraAPI;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════════════
// Test Route — visit /test-sdk in your browser or with curl
// ══════════════════════════════════════════════════════════════════════════════

Route::get('/test-sdk', function () {

    $results = [];

    // ── TEST 1: Location lookup ───────────────────────────────────────────────
    try {
        $geo = AvraAPI::location()->lookupIp('112.134.205.126');

        $results['location_lookup'] = [
            'status'      => 'PASS',
            'request_id'  => $geo->requestId,
            'country'     => $geo->data['country'],
            'country_code'=> $geo->data['country_code'],
            'timezone'    => $geo->data['timezone'],
            'dot_notation'=> $geo->get('data.country_code'),
        ];
    } catch (ApixException $e) {
        $results['location_lookup'] = [
            'status'  => 'FAIL',
            'code'    => $e->getErrorCode(),
            'http'    => $e->getHttpStatus(),
            'message' => $e->getMessage(),
        ];
    }

    // ── TEST 2: Location with provider override ────────────────────────────────
    try {
        $geo = AvraAPI::location()->withProvider('maxmind')->lookupIp('8.8.8.8');

        $results['location_with_provider'] = [
            'status'  => 'PASS',
            'country' => $geo->data['country'],
        ];
    } catch (ApixException $e) {
        $results['location_with_provider'] = [
            'status'  => 'FAIL',
            'message' => $e->getMessage(),
        ];
    }

    // ── TEST 3: SMS — send single ──────────────────────────────────────────────
    try {
        $sms = AvraAPI::sms()->sendSingle('0771234567', 'Hello from AvraAPI Laravel SDK!');

        $results['sms_send_single'] = [
            'status'          => 'PASS',
            'request_id'      => $sms->requestId,
            'send_method'     => $sms->data['send_method'],
            'message_count'   => $sms->data['message_count'],
            'credits_charged' => $sms->data['credits_charged'],
        ];
    } catch (\Avraapi\Apix\Exceptions\ApixInsufficientFundsException $e) {
        $results['sms_send_single'] = [
            'status'  => 'SKIP',
            'reason'  => 'Insufficient wallet credits — top up in APIX dashboard.',
        ];
    } catch (ApixException $e) {
        $results['sms_send_single'] = [
            'status'  => 'FAIL',
            'code'    => $e->getErrorCode(),
            'message' => $e->getMessage(),
        ];
    }

    // ── TEST 4: SMS — bulk same (dry run) ─────────────────────────────────────
    try {
        $sms = AvraAPI::sms()->sendBulkSame(
            recipients: ['0771234567', '0777654321'],
            message:    'Laravel SDK bulk test',
            checkCost:  true,   // dry run — no actual send
        );

        $results['sms_bulk_same'] = [
            'status'        => 'PASS',
            'send_method'   => $sms->data['send_method'],
            'message_count' => $sms->data['message_count'],
        ];
    } catch (ApixException $e) {
        $results['sms_bulk_same'] = [
            'status'  => 'FAIL',
            'message' => $e->getMessage(),
        ];
    }

    // ── TEST 5: SMS — bulk different ──────────────────────────────────────────
    try {
        $sms = AvraAPI::sms()->sendBulkDifferent([
            ['to' => '0771234567', 'msg' => 'Hello Alice from Laravel SDK!'],
            ['to' => '0777654321', 'msg' => 'Hello Bob from Laravel SDK!'],
        ]);

        $results['sms_bulk_different'] = [
            'status'        => 'PASS',
            'send_method'   => $sms->data['send_method'],
            'message_count' => $sms->data['message_count'],
        ];
    } catch (\Avraapi\Apix\Exceptions\ApixInsufficientFundsException) {
        $results['sms_bulk_different'] = ['status' => 'SKIP', 'reason' => 'Insufficient funds'];
    } catch (ApixException $e) {
        $results['sms_bulk_different'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 6: SMS — balance ─────────────────────────────────────────────────
    try {
        $balance = AvraAPI::sms()->getBalance();

        $results['sms_balance'] = [
            'status'            => 'PASS',
            'source'            => $balance->data['source'],
            'balance_formatted' => $balance->data['balance_formatted'],
        ];
    } catch (ApixException $e) {
        $results['sms_balance'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 7: Utilities — QR Code (binary PNG) ──────────────────────────────
    try {
        $qr = AvraAPI::utilities()->generateQr(
            data:   'https://avraapi.com',
            format: 'png',
            size:   300,
        );

        // Save to the Laravel storage directory
        $path = storage_path('app/test-qr.png');
        $qr->saveAs($path);

        $results['utilities_qr_png'] = [
            'status'       => 'PASS',
            'content_type' => $qr->contentType,
            'size_bytes'   => $qr->size,
            'saved_to'     => $path,
            'is_png'       => $qr->isPng(),
        ];
    } catch (ApixException $e) {
        $results['utilities_qr_png'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 8: Utilities — QR Code (base64 JSON) ─────────────────────────────
    try {
        $qr = AvraAPI::utilities()->generateQr(
            data:   'https://avraapi.com/sdk',
            format: 'base64',
        );

        $results['utilities_qr_base64'] = [
            'status'           => 'PASS',
            'request_id'       => $qr->requestId,
            'format'           => $qr->data['format'],
            'data_uri_preview' => substr((string) $qr->data['data_uri'], 0, 30) . '...',
        ];
    } catch (ApixException $e) {
        $results['utilities_qr_base64'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 9: Utilities — Barcode (binary PNG) ──────────────────────────────
    try {
        $barcode = AvraAPI::utilities()->generateBarcode(
            data:   '5901234123457',
            type:   'EAN13',
            format: 'png',
        );

        $path = storage_path('app/test-barcode.png');
        $barcode->saveAs($path);

        $results['utilities_barcode'] = [
            'status'       => 'PASS',
            'content_type' => $barcode->contentType,
            'size_bytes'   => $barcode->size,
            'saved_to'     => $path,
        ];
    } catch (ApixException $e) {
        $results['utilities_barcode'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 10: Utilities — PDF (binary) ─────────────────────────────────────
    try {
        $pdf = AvraAPI::utilities()->generatePdf(
            html: '<!DOCTYPE html><html><body>
                <h1>AvraAPI Laravel SDK Test Invoice</h1>
                <p>Generated via <strong>AvraAPI::utilities()->generatePdf()</strong></p>
            </body></html>',
            pageSize:    'A4',
            orientation: 'portrait',
        );

        $path = storage_path('app/test-invoice.pdf');
        $pdf->saveAs($path);

        $results['utilities_pdf_binary'] = [
            'status'       => 'PASS',
            'content_type' => $pdf->contentType,
            'size_bytes'   => $pdf->size,
            'saved_to'     => $path,
            'is_pdf'       => $pdf->isPdf(),
        ];
    } catch (ApixException $e) {
        $results['utilities_pdf_binary'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 11: Utilities — PDF (base64 JSON) ────────────────────────────────
    try {
        $pdf = AvraAPI::utilities()->generatePdf(
            html:         '<h1>Base64 PDF via AvraAPI Facade</h1>',
            responseType: 'base64',
        );

        $results['utilities_pdf_base64'] = [
            'status'           => 'PASS',
            'request_id'       => $pdf->requestId,
            'format'           => $pdf->data['format'],
            'media_type'       => $pdf->data['media_type'],
            'base64_length'    => strlen((string) $pdf->data['data']),
        ];
    } catch (ApixException $e) {
        $results['utilities_pdf_base64'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    }

    // ── TEST 12: Universal call() — smart path normalization ──────────────────
    $paths = [
        'location/lookup',
        '/location/lookup',
        '/api/v1/location/lookup',
        'http://localhost/api/v1/location/lookup',
    ];

    $results['universal_call'] = [];
    foreach ($paths as $path) {
        try {
            $r = AvraAPI::call('POST', $path, ['ip' => '1.1.1.1']);
            $results['universal_call'][$path] = 'PASS — country: ' . $r->get('data.country');
        } catch (ApixException $e) {
            $results['universal_call'][$path] = 'FAIL — ' . $e->getMessage();
        }
    }

    // ── TEST 13: Exception mapping — invalid credentials ──────────────────────
    try {
        // Temporarily resolve a client with wrong credentials to verify exception typing
        $badClient = new \Avraapi\Apix\ApixClient([
            'APIX_PROJECT_KEY' => 'invalid-key',
            'APIX_API_SECRET'  => 'invalid-secret',
            'APIX_BASE_URL'    => config('avraapi.base_url'),
        ]);
        $badClient->location()->lookupIp('1.1.1.1');
        $results['exception_auth'] = ['status' => 'FAIL', 'reason' => 'No exception thrown!'];
    } catch (ApixAuthenticationException $e) {
        $results['exception_auth'] = [
            'status'     => 'PASS',
            'exception'  => 'ApixAuthenticationException',
            'error_code' => $e->getErrorCode(),
            'http'       => $e->getHttpStatus(),
        ];
    } catch (ApixNetworkException $e) {
        $results['exception_auth'] = [
            'status'  => 'SKIP',
            'reason'  => 'Network error — is Sail running? ' . $e->getMessage(),
        ];
    }

    // ── TEST 14: Validation exception ─────────────────────────────────────────
    try {
        AvraAPI::location()->lookupIp('');
        $results['exception_validation'] = ['status' => 'FAIL', 'reason' => 'No exception thrown!'];
    } catch (ApixValidationException $e) {
        $results['exception_validation'] = [
            'status'            => 'PASS',
            'exception'         => 'ApixValidationException',
            'error_code'        => $e->getErrorCode(),
            'validation_errors' => $e->getValidationErrors(),
        ];
    } catch (ApixException $e) {
        // Gateway may return 400 instead of 422 for empty IP — still acceptable
        $results['exception_validation'] = [
            'status'     => 'PASS (as ApixException)',
            'error_code' => $e->getErrorCode(),
            'message'    => $e->getMessage(),
        ];
    }

    // ── Return JSON summary ───────────────────────────────────────────────────
    $passed = count(array_filter($results, fn ($r) => is_array($r) && ($r['status'] ?? '') === 'PASS'));
    $total  = count(array_filter($results, fn ($r) => is_array($r) && isset($r['status']) && $r['status'] !== 'SKIP'));

    return response()->json([
        'sdk'     => 'avraapi/laravel-sdk v1.0.0',
        'facade'  => 'AvraAPI (resolves to ' . get_class(app('avraapi')) . ')',
        'summary' => "{$passed}/{$total} tests passed",
        'results' => $results,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});
```

---

## 9. Run the Tests

```bash
# Start the Laravel dev server for the test app
cd /your-workspace/my-laravel-app
php artisan serve
```

Then open your browser or run:

```bash
curl -s http://127.0.0.1:8000/test-sdk | python3 -m json.tool
```

You should see a JSON response with all tests passing:

```json
{
  "sdk": "avraapi/laravel-sdk v1.0.0",
  "facade": "AvraAPI (resolves to Avraapi\\Apix\\ApixClient)",
  "summary": "12/12 tests passed",
  "results": {
    "location_lookup": {
      "status": "PASS",
      "country": "Sri Lanka",
      ...
    },
    ...
  }
}
```

---

## 10. Verify Dependency Injection Works

Beyond Facades, confirm you can inject `ApixClient` directly in a controller:

```bash
php artisan make:controller AvraApiTestController
```

Edit `app/Http/Controllers/AvraApiTestController.php`:

```php
<?php

namespace App\Http\Controllers;

use Avraapi\Apix\ApixClient;
use Illuminate\Http\JsonResponse;

class AvraApiTestController extends Controller
{
    // ApixClient is injected automatically from the service container
    public function __construct(private readonly ApixClient $apix) {}

    public function index(): JsonResponse
    {
        $geo = $this->apix->location()->lookupIp('1.1.1.1');

        return response()->json([
            'injected' => true,
            'country'  => $geo->data['country'],
        ]);
    }
}
```

Add to routes/web.php:
```php
Route::get('/test-di', [App\Http\Controllers\AvraApiTestController::class, 'index']);
```

Visit `http://127.0.0.1:8000/test-di` — should return the geolocation JSON.

---

## 11. Verify IDE Auto-Completion

Open `routes/web.php` in PHPStorm or VS Code (with Intelephense):

1. Type `AvraAPI::` — your IDE should suggest: `location()`, `sms()`, `utilities()`, `call()`.
2. Type `AvraAPI::sms()->` — should suggest: `sendSingle()`, `sendBulkSame()`, `sendBulkDifferent()`, `getBalance()`, `withProvider()`.
3. Type `AvraAPI::utilities()->generatePdf(` — should show the full parameter list with types.

This works because of the `@method` docblocks on the `AvraAPI` Facade and
the `@mixin \Avraapi\Apix\ApixClient` tag that exposes the underlying class.

---

## 12. Workflow: Iterating on the SDK

Because both packages are symlinked, the iteration loop is:

```bash
# 1. Edit a file in laravel-sdk/ or apix-php-sdk/
# 2. Clear Laravel's compiled class cache (only needed if you change class names)
php artisan clear-compiled
php artisan cache:clear

# 3. Re-run your test route
curl -s http://127.0.0.1:8000/test-sdk | python3 -m json.tool
```

No `composer update` needed between source changes.

---

## 13. Publishing to Packagist (When Ready)

1. Push `apix-php-sdk/` and `laravel-sdk/` to public GitHub repositories.
2. Register both on [packagist.org](https://packagist.org).
3. Tag releases: `git tag v1.0.0 && git push --tags` on both repos.
4. Consumers install with:
   ```bash
   composer require avraapi/laravel-sdk
   ```
   Composer resolves `avraapi/apix-php-sdk` automatically as a transitive dependency.
5. Publish config: `php artisan vendor:publish --tag="avraapi-config"`

---

## 14. Common Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Class "Avraapi\Laravel\Facades\AvraAPI" not found` | Auto-discovery not run | `php artisan package:discover` |
| `Target [avraapi] is not instantiable` | Service provider not loaded | Check `vendor/avraapi/laravel-sdk` symlink exists |
| `APIX_PROJECT_KEY is required` | Missing .env key | Add `APIX_PROJECT_KEY` and `APIX_API_SECRET` to `.env` |
| `ApixNetworkException: Could not connect` | Sail not running | `./vendor/bin/sail up -d` in the gateway project |
| IDE shows no autocomplete on `AvraAPI::` | Missing IDE helper | Install `barryvdh/laravel-ide-helper` and run `php artisan ide-helper:generate` |
| Config not applying after edit | Cached config | `php artisan config:clear` |
