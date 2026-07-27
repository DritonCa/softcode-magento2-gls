# Changelog

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
