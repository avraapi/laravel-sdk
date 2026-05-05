<?php

// File: src/Facades/AvraAPI.php

declare(strict_types=1);

namespace Avraapi\Laravel\Facades;

use Avraapi\Apix\Responses\ApiResponse;
use Avraapi\Apix\Responses\BinaryResponse;
use Avraapi\Apix\Services\LocationService;
use Avraapi\Apix\Services\SmsService;
use Avraapi\Apix\Services\UtilitiesService;
use Illuminate\Support\Facades\Facade;

/**
 * AvraAPI Laravel Facade
 *
 * Provides static proxy access to the underlying ApixClient singleton
 * registered in the Laravel service container by AvraApiServiceProvider.
 *
 * Auto-discovered via `extra.laravel.aliases` in composer.json — no manual
 * alias registration in config/app.php is required.
 *
 * ── Quick Start ───────────────────────────────────────────────────────────────
 *
 *   use Avraapi\Laravel\Facades\AvraAPI;
 *
 *   // Or use the auto-alias (no import needed in controllers):
 *   AvraAPI::location()->lookupIp('112.134.205.126');
 *
 * ── Service Group Overview ────────────────────────────────────────────────────
 *
 *   AvraAPI::location()   — IP geolocation / intelligence lookups
 *   AvraAPI::sms()        — SMS messaging (single, bulk-same, bulk-different, balance)
 *   AvraAPI::utilities()  — QR codes, barcodes, PDF generation
 *   AvraAPI::call()       — Universal escape hatch for any APIX endpoint
 *
 * ── Provider Override (fluent, per-request) ───────────────────────────────────
 *
 *   AvraAPI::sms()->withProvider('quicksend')->sendSingle('0771234567', 'Hello!');
 *   AvraAPI::location()->withProvider('maxmind')->lookupIp('1.1.1.1');
 *
 * ── Universal Call (future-proof escape hatch) ────────────────────────────────
 *
 *   // Smart path normalization — all of these resolve to the same endpoint:
 *   AvraAPI::call('POST', 'sms/send', $payload);
 *   AvraAPI::call('POST', '/sms/send', $payload);
 *   AvraAPI::call('POST', '/api/v1/sms/send', $payload);
 *   AvraAPI::call('POST', 'https://avraapi.com/api/v1/sms/send', $payload);
 *
 * ── @method Docblocks ─────────────────────────────────────────────────────────
 *
 * The @method tags below expose all public methods of the underlying
 * ApixClient class to IDE static analysis tools (PHPStorm, VS Code + Intelephense,
 * Psalm, PHPStan). Without these, IDEs cannot see through the Facade magic.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ── Service Group Accessors ───────────────────────────────────────────────────
 *
 * @method static LocationService  location()  Access the Location service group (IP geolocation).
 * @method static SmsService       sms()       Access the SMS service group (messaging & balance).
 * @method static UtilitiesService utilities() Access the Utilities service group (QR, barcode, PDF).
 *
 * ── Universal Call ────────────────────────────────────────────────────────────
 *
 * @method static ApiResponse|BinaryResponse call(string $method, string $path, array $payload = []) Make a raw call to any APIX endpoint. Accepts any path format — full URL, /api/v1/... prefix, or bare path. Only 'POST' dispatches; others throw InvalidArgumentException.
 *
 * ── Location Service: lookupIp ────────────────────────────────────────────────
 *
 * Resolves an IPv4 or IPv6 address to geographic and ISP metadata using the
 * MaxMind GeoLite2 provider.
 *
 * Fluent provider override:
 *   AvraAPI::location()->withProvider('maxmind')->lookupIp('1.1.1.1');
 *
 * Response data keys:
 *   ->data['country']      — e.g. 'Sri Lanka'
 *   ->data['country_code'] — e.g. 'LK'
 *   ->data['city']         — nullable string
 *   ->data['isp']          — nullable string
 *   ->data['latitude']     — float
 *   ->data['longitude']    — float
 *   ->data['timezone']     — e.g. 'Asia/Colombo'
 *
 * ── SMS Service: sendSingle ───────────────────────────────────────────────────
 *
 * Sends a single SMS to one recipient. Maps to POST /sms/send { send_method: "single" }.
 *
 * Response data keys:
 *   ->data['send_method']       — 'single'
 *   ->data['message_count']     — int (should be 1)
 *   ->data['credits_charged']   — int
 *   ->data['provider_response'] — array (raw QuickSend response)
 *
 * ── SMS Service: sendBulkSame ─────────────────────────────────────────────────
 *
 * Sends the same message to many recipients. Maps to POST /sms/send { send_method: "bulk_same" }.
 * When $checkCost is true, returns cost info without dispatching messages (dry run).
 *
 * Response data keys:
 *   ->data['send_method']       — 'bulk_same'
 *   ->data['message_count']     — int
 *   ->data['credits_charged']   — int
 *   ->data['provider_response'] — array
 *
 * ── SMS Service: sendBulkDifferent ────────────────────────────────────────────
 *
 * Sends a different message to each recipient. Maps to POST /sms/send { send_method: "bulk_different" }.
 * Maximum 20 entries per request (gateway limit). Each entry: ['to' => '077...', 'msg' => '...'].
 *
 * ── SMS Service: getBalance ───────────────────────────────────────────────────
 *
 * Checks the QuickSend.lk SMS balance. Always FREE — no wallet credits deducted.
 *
 * Response data keys:
 *   ->data['source']            — 'quicksend_direct' or 'apix_wallet'
 *   ->data['balance_formatted'] — string (e.g. '1500')
 *   ->data['provider_response'] — array|null
 *
 * ── Utilities Service: generateQr ─────────────────────────────────────────────
 *
 * Generates a QR code. Returns BinaryResponse for 'png'/'svg' formats,
 * ApiResponse for 'base64' format (JSON with data_uri key).
 *
 * Binary usage:   AvraAPI::utilities()->generateQr('https://avraapi.com')->saveAs('/tmp/qr.png');
 * Base64 usage:   AvraAPI::utilities()->generateQr('...', format: 'base64')->data['data_uri']
 *
 * ── Utilities Service: generateBarcode ────────────────────────────────────────
 *
 * Generates a barcode image. Always returns BinaryResponse (no base64 mode).
 * Supported types: C128, C128A, C128B, C128C, EAN13, EAN8, UPCA, UPCE,
 *                  C39, C39+, I25, ITF14, MSI, POSTNET.
 *
 * Usage: AvraAPI::utilities()->generateBarcode('APIX-2026', type: 'C128')->saveAs('/tmp/bc.png');
 *
 * ── Utilities Service: generatePdf ────────────────────────────────────────
 *
 * Converts HTML to PDF. Returns BinaryResponse for 'binary' response_type (default),
 * ApiResponse for 'base64' response_type (JSON with data and media_type keys).
 *
 * Now supports Base64 input mode via `isBase64: true` and privacy mode via `privacyMode: true`.
 *
 * Binary usage:       AvraAPI::utilities()->generatePdf('<h1>Invoice</h1>')->saveAs('/tmp/inv.pdf');
 * Base64 input:       AvraAPI::utilities()->generatePdf(base64_encode($html), isBase64: true)->saveAs('/tmp/inv.pdf');
 * Base64 response:    AvraAPI::utilities()->generatePdf('...', responseType: 'base64')->data['data']
 * Privacy mode:       AvraAPI::utilities()->generatePdf($html, privacyMode: true)->saveAs('/tmp/inv.pdf');
 *
 * ── Utilities Service: generatePdfFromBase64 ────────────────────────────
 *
 * Convenience wrapper that auto-encodes raw HTML to Base64 before sending.
 * Recommended for complex templates (invoices, reports with inline CSS)
 * to avoid JSON escaping issues with quotes, newlines, and special characters.
 *
 * The method accepts raw HTML — encoding is handled internally by the SDK.
 * The 512 KB size limit applies to the decoded HTML, not the encoded payload.
 *
 * Usage:
 *   $html = file_get_contents(resource_path('templates/invoice.html'));
 *   AvraAPI::utilities()->generatePdfFromBase64($html)->saveAs('/tmp/invoice.pdf');
 *
 *   // With options:
 *   AvraAPI::utilities()->generatePdfFromBase64(
 *       html:        $html,
 *       pageSize:    'Letter',
 *       orientation: 'landscape',
 *       margins:     ['top' => 15, 'right' => 20, 'bottom' => 15, 'left' => 20],
 *       privacyMode: true,
 *   )->saveAs(storage_path('app/invoices/inv-001.pdf'));
 *
 * ── Exceptions thrown by the underlying SDK ───────────────────────────────────
 *
 * All service method exceptions propagate naturally through the Facade and
 * can be caught in your controllers or registered in Laravel's exception handler:
 *
 *   \Avraapi\Apix\Exceptions\ApixAuthenticationException  — HTTP 401 (invalid credentials)
 *   \Avraapi\Apix\Exceptions\ApixInsufficientFundsException — HTTP 402 (wallet depleted)
 *   \Avraapi\Apix\Exceptions\ApixValidationException      — HTTP 422 (bad request payload)
 *   \Avraapi\Apix\Exceptions\ApixRateLimitException       — HTTP 429 (rate limit exceeded)
 *   \Avraapi\Apix\Exceptions\ApixServiceUnavailableException — HTTP 503 (project kill-switch)
 *   \Avraapi\Apix\Exceptions\ApixNetworkException         — Transport failure (no response)
 *   \Avraapi\Apix\Exceptions\ApixException                — Base — catch all APIX errors
 *
 * ── Registering exceptions in Laravel's Handler ───────────────────────────────
 *
 *   // In app/Exceptions/Handler.php (or bootstrap/app.php for Laravel 11+):
 *
 *   use Avraapi\Apix\Exceptions\ApixValidationException;
 *   use Avraapi\Apix\Exceptions\ApixAuthenticationException;
 *   use Avraapi\Apix\Exceptions\ApixException;
 *
 *   $exceptions->render(function (ApixValidationException $e) {
 *       return response()->json([
 *           'error'   => $e->getErrorCode(),
 *           'message' => $e->getMessage(),
 *           'fields'  => $e->getValidationErrors(),
 *       ], 422);
 *   });
 *
 *   $exceptions->render(function (ApixException $e) {
 *       return response()->json([
 *           'error'      => $e->getErrorCode(),
 *           'message'    => $e->getMessage(),
 *           'request_id' => $e->getRequestId(),
 *       ], $e->getHttpStatus() ?: 500);
 *   });
 *
 * @see \Avraapi\Apix\ApixClient           The underlying singleton class.
 * @see \Avraapi\Apix\Services\SmsService
 * @see \Avraapi\Apix\Services\LocationService
 * @see \Avraapi\Apix\Services\UtilitiesService
 * @see \Avraapi\Apix\Responses\ApiResponse
 * @see \Avraapi\Apix\Responses\BinaryResponse
 *
 * @mixin \Avraapi\Apix\ApixClient
 */
final class AvraAPI extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * This string must match the abstract key used in AvraApiServiceProvider::register()
     * when the ApixClient alias is bound:
     *   $this->app->alias(ApixClient::class, 'avraapi');
     *
     * The Facade base class calls app('avraapi') to resolve the singleton.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'avraapi';
    }
}
