# Changelog

## [Unreleased]
### Added
- **Unit tests now run in CI** as a real gate — a standalone `Test/bootstrap.php`
  autoloads the module and stubs the mocked Magento contracts, so
  `phpunit -c phpunit.xml.dist` runs without a Magento install.
- `Test/Unit/Model/ConfigTest.php` — a unit test that specifies the GLS
  delivery-method rules (carrier on/off, only-enabled methods, stable ordering,
  and `getMethodPrice()` returning `0.0` for free vs `null` for a disallowed
  method).
### Changed
- CI now **fails** on Magento 2 coding-standard errors: removed the `|| true`
  that silently swallowed `phpcs` failures and added `-n` so only errors (not
  warnings) break the build.

## [1.1.0]
### Fixed
- Validate the GLS method server-side against the enabled configured methods, so
  a forged request can no longer set an unknown method and get free shipping.
- Look up the shipping price centrally; an unknown method no longer falls through
  to zero shipping.
- Call the GLS shop-finder API over HTTPS instead of HTTP.
- Log exceptions server-side and return generic messages to the frontend.
### Added
- CSRF (form-key) validation on the save endpoint; composer.json, CI, README.
