# Softcode GLS Shipping for Magento 2

Free, lightweight Magento 2 module that adds **GLS shipping intelligence**
to **any checkout** using **simple CSS selectors and JS hooks**.

Built for **custom checkouts**, **B2B flows**, and **real-world logistics**.

---

## ✨ Features

- 🚚 **GLS Home, Business & ParcelShop delivery**
- 🏪 **ParcelShop selector support**
- 🎯 **Selector-based integration**
- 🔁 **Live saving of GLS data to quote**
- 💰 **Custom GLS shipping price calculation**
- 🔐 **Server-side validation**
- 🧠 **Quote → Order GLS data mapping**
- 🆓 **100% free & open source**

---

## 🧱 Supported setups

✔ Luma checkout  
✔ Custom checkouts  
✔ Headless storefronts

✔ B2C & B2B shops  
✔ Public sector orders (EAN)

> This module does **not** depend on Magento Checkout JS internals.

---

## 🧪 Tested with

This module has been **developed and tested in production**
together with the following setup:

- Custom checkout built on top of `Softcode_CheckoutOverride`
- B2B / B2C hybrid flows
- GLS Home, Business & ParcelShop delivery
- Real ParcelShop selection and validation
- Real order placement with GLS data persisted on the order

This ensures the module is **not theoretical**, but built and verified
in a **real checkout flow**.

---

## 🧠 How it works

1. Customer selects a GLS delivery method
2. If `gls_shop` is selected:
    - A ParcelShop must be chosen
3. Selection is saved on the **quote**
4. Validation happens **server-side**
5. GLS data is copied to the **order**

Invalid GLS selections **cannot be ordered**.

---

## 🎯 Required selectors

The module integrates using **plain CSS selectors**.

### Delivery method selector
```html
<input type="radio" name="delivery_method" value="gls_home">
<input type="radio" name="delivery_method" value="gls_business">
<input type="radio" name="delivery_method" value="gls_shop">
