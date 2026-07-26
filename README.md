# Softcode_Gls

A Magento 2 module that adds **GLS shipping** to the checkout: GLS Home, Business
and ParcelShop delivery, a parcel-shop selector, and its own shipping-price rule.
It attaches to any checkout — Luma, a custom one-page, or headless — through small
JS hooks, and it persists the chosen GLS option all the way from quote to order.

---

## Requirements

- Magento **2.4.x**
- PHP **8.1** or **8.2**

## Installation

**With Composer (recommended)**

```bash
composer require softcode/module-gls
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

**Manually**

Copy the module to `app/code/Softcode/Gls`, then run the same three commands.

## Configuration

Enable the module and its carrier under
**Stores → Configuration → Sales → Shipping Methods → Softcode GLS**.
The carrier code is `softcode_gls`; access is guarded by the module's ACL.

---

## How it works

- **Carrier** (`Model/Carrier/Gls`) registers GLS as a shipping method so it shows
  up in the quote's shipping rates.
- **Price rule** (`Model/Quote/Address/Total/GlsShipping`) sets the GLS shipping
  amount as a quote total, so the price is calculated server-side, not in the UI.
- **Frontend** (`gls-checkout.js`) reads the customer's choice and talks to the
  module's controllers to look up parcel shops and save the selection to the quote.

```
Checkout (gls-checkout.js)
   │  AJAX
   ▼
GLS controllers ──▶ Quote (GLS method + shop) ──▶ GlsShipping total ──▶ Order
```

### Endpoints

| Method | Route | Purpose |
| --- | --- | --- |
| `GET`  | `/gls/index/methods` | Available GLS delivery methods |
| `GET`  | `/gls/index/getglslist` | Nearby parcel shops for a postcode |
| `GET`  | `/gls/index/selected` | The currently selected GLS option |
| `POST` | `/gls/index/save` | Save the chosen method / parcel shop to the quote |

Each controller declares its HTTP verb via `HttpGetActionInterface` /
`HttpPostActionInterface`, per Magento conventions.

---

## Notes for integrators

- The parcel-shop list is looked up live; point it at your GLS data source in
  `Controller/Index/getGlsList`.
- As with any custom AJAX checkout, send Magento's `form_key` with the `save` POST
  and validate it (`CsrfAwareActionInterface` + `FormKey\Validator`) before going
  to production.

---

## License

MIT — see [LICENSE](LICENSE).
