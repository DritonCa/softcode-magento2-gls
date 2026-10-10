# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-10-10
### Added
- **GLS labels from the admin order view** (`Create GLS Label` / `Download`), calling
  the GLS Denmark CreateShipment API. Labels are stored under
  `media/softcode/gls/labels`; heavy orders are split into parcels under a
  configurable weight limit with a combined PDF (Zend_Pdf) or a ZIP fallback.
- **Ship when invoiced**: creates the shipment with GLS tracking numbers and sends
  Magento's shipment email; also runs when a label is created on an invoiced order.
  Opt out per invoice with `gls_skip_auto_shipment`.
- **Tracking links** in the shipment email and customer account (carrier tracking support).
- **Shipping rules** (`Api\ItemShippingRuleInterface`, `Model\Shippability`): virtual and
  downloadable products and configured SKUs never ship; projects add their own rules via DI.
  A cart with nothing to ship gets a free `softcode_gls_none` rate.
- **Recipient resolver** (`Api\RecipientResolverInterface`) with a default that uses the
  order's shipping address.
- Parcel-shop name and address are stored on quote and order and shown on the admin order view.
- `fieldset.xml` copies the GLS fields from quote to order, so the module no longer relies
  on a checkout module to do it.
- `/gls/index/methods` reports `needs_shipping`; JS events `softcode:gls:rendered` and
  `softcode:gls:saved`.
### Changed
- The API password is stored encrypted (plain-text values from 1.x still work).
- Admin labels are English and translatable; config fields are scoped to default/website.
- Checkout JS builds DOM nodes instead of HTML strings, aborts superseded saves and restores
  the saved parcel shop after the asynchronous shop list has loaded.
- CI runs the unit tests on PHP 8.1–8.4.
### Fixed
- A pre-selected parcel shop shown in the dropdown is now saved; previously the cart had
  no shop and the order was rejected.
- An empty parcel-shop save can no longer overwrite a real choice that arrived first.
- Parcel-shop names from the GLS API are no longer inserted as HTML.

## Earlier unreleased changes, included in 2.0.0
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
