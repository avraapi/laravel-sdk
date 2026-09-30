<p align="center">
  <a href="https://avraapi.com">
    <img src="https://avraapi.com/images/logo/web-logo-with-icon-full-home.svg" alt="AvraAPI" height="72">
  </a>
</p>

<p align="center">
  <a href="https://docs.avraapi.com/sdk/overview"><img src="https://img.shields.io/badge/AvraAPI-Official%20Laravel%20SDK-1666FF?style=for-the-badge" alt="Official AvraAPI Laravel SDK"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-10%2F11%2F12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 10, 11, and 12"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2 or later"></a>
  <a href="https://docs.avraapi.com/universal-payment-gateway/overview"><img src="https://img.shields.io/badge/Universal%20Payment%20Gateway-Available-16A34A?style=for-the-badge" alt="Universal Payment Gateway available"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-0F766E?style=for-the-badge" alt="MIT License"></a>
</p>

<h1 align="center">AvraAPI Laravel SDK</h1>

<p align="center">
  The official Laravel integration package for <a href="https://avraapi.com">AvraAPI</a>.
  It brings AvraAPI into Laravel through configuration, dependency injection, and a Facade backed by the official PHP SDK.
</p>

<p align="center">
  <a href="https://docs.avraapi.com"><strong>Read the documentation</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://docs.avraapi.com/sdk/overview"><strong>SDK guidelines</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://avraapi.com"><strong>Visit AvraAPI</strong></a>
</p>

## About AvraAPI

AvraAPI gives Laravel applications one reliable way to work with essential
digital services and configured providers. Your application stays in control of
its users, data, order records, and business decisions, while the SDK provides
a consistent provider integration experience.

This package supports Laravel 10, 11, and 12 on PHP 8.2 or later. It registers
the official PHP SDK as a Laravel singleton, supports dependency injection, and
provides the `AvraAPI` Facade. Complete setup, credential safety, service
guidance, and current capabilities are maintained in the
[AvraAPI Documentation](https://docs.avraapi.com).

## Services

| Service | Built for |
| --- | --- |
| Currency | Currency codes, live exchange rates, conversions, and CBSL rate information. |
| Location | IP address location details for applications that need geographic context. |
| Security | VPN, proxy, hosting, relay, and disposable-email checks. |
| SMS | Single and bulk messaging through configured SMS providers. |
| Utilities | PDF, QR code, and barcode generation. |

Each service uses the providers configured for your AvraAPI project. Review the
[service guidelines](https://docs.avraapi.com) before enabling a service in a
Laravel application.

## Advanced Service: Universal Payment Gateway

The Universal Payment Gateway (UPG) is available through the Laravel
integration's PHP SDK client. Use dependency injection or the Facade for the
server-side payment service while keeping checkout completion, callbacks,
gateway credentials, and fulfilment decisions in your backend.

UPG supports configured providers such as PayHere, MarxPay, DirectPay, OnePay,
WebXPay, KOKO, PayPlus, and Stripe. Availability and capabilities always depend
on the project and configured gateway.

For checkout guidance, Payment Elements, completion safety, and
provider-specific requirements, start with the
[Universal Payment Gateway documentation](https://docs.avraapi.com/universal-payment-gateway/overview).

## Documentation and guidelines

The documentation is the source of truth for setup and integration guidance:

- [AvraAPI Documentation](https://docs.avraapi.com)
- [SDK Overview](https://docs.avraapi.com/sdk/overview)
- [REST API Reference](https://docs.avraapi.com/api-reference/rest-api)
- [Universal Payment Gateway](https://docs.avraapi.com/universal-payment-gateway/overview)

## Developed by

Built and maintained by [Fidex Developers (Pvt) Ltd](https://fidex.lk) for the
AvraAPI platform.

## License

AvraAPI Laravel SDK is open-sourced under the [MIT License](LICENSE).
