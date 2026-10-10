/**
 * GLS delivery methods and parcel-shop picker for a custom checkout.
 *
 * Renders into window.softcode.delivery.mountPoint and saves the choice to the quote.
 * Events for project code:
 *   softcode:gls:rendered  (wrapper)   after the methods are drawn, e.g. to reorder them
 *   softcode:gls:saved     (method)    after a choice was stored on the quote
 */
require(['jquery'], function ($) {
    'use strict';

    $(function () {
        if (!window.softcode || !window.softcode.delivery) return;
        if (!window.softcode.gls || !window.softcode.gls.enabled) return;

        const mount = $(window.softcode.delivery.mountPoint);
        if (!mount.length) return;

        let isFetching = false;
        let saveXhr = null;
        let availableShops = [];
        // Remembered separately because the shop list loads asynchronously.
        let selectedShopId = '';

        function formatPrice(price) {
            return Number(price).toFixed(2).replace('.', ',') + ' kr.';
        }

        function selectedMethod() {
            return $('input[name="delivery_method"]:checked').val() || '';
        }

        /* Methods */
        $.getJSON('/gls/index/methods', function (resp) {
            if (!resp.success || !resp.methods || !resp.methods.length) return;

            const wrapper = $('<div class="sc-gls-wrapper"/>');
            resp.methods.forEach(function (method) {
                const label = $('<label class="sc-gls-method"/>');
                label.append($('<input type="radio" name="delivery_method"/>').val(method.code));
                label.append(document.createTextNode(' ' + method.label + ' '));
                label.append($('<strong/>').text(formatPrice(method.price)));
                wrapper.append(label, '<br/>');
            });

            const shopContainer = $('<div id="sc-gls-shop-container" style="display:none; margin-top:10px"/>');
            shopContainer.append(
                $('<div id="sc-gls-shop-error" class="text-danger small mb-2" style="display:none"/>')
                    .text('Indtast vejnavn og postnummer for at se pakkeshops'),
                $('<select id="sc-gls-shop-select" style="max-width:350px"/>')
                    .append($('<option value=""/>').text('Indtast adresse og postnr…'))
            );
            wrapper.append(shopContainer);

            mount.append(wrapper);
            $(document).trigger('softcode:gls:rendered', [wrapper]);
            restoreSelection();

            $(document).on('change', 'input[name="delivery_method"]', function () {
                if ($(this).val() === 'gls_shop') {
                    $('#sc-gls-shop-container').show();
                    fetchShops();
                } else {
                    $('#sc-gls-shop-container').hide();
                }
                saveSelection();
            });
        });

        /* Address fields (main + alternative delivery address) */
        $('#sc-address-street, #sc-address-postcode, #sc-alt-street, #sc-alt-postcode, #sc-use-alt-address')
            .on('input change', debounce(function () {
                if (selectedMethod() === 'gls_shop') {
                    fetchShops();
                }
            }, 400));

        function getActiveAddress() {
            const prefix = $('#sc-use-alt-address').is(':checked') ? '#sc-alt-' : '#sc-address-';
            return {
                street: ($(prefix + 'street').val() || '').trim(),
                postcode: ($(prefix + 'postcode').val() || '').trim()
            };
        }

        /* Parcel shops */
        function fetchShops() {
            if (isFetching || selectedMethod() !== 'gls_shop') return;

            const address = getActiveAddress();
            if (!address.street || address.postcode.length < 4) {
                $('#sc-gls-shop-error').show();
                $('#sc-gls-shop-select').empty()
                    .append($('<option value=""/>').text('Indtast adresse og postnr…'));
                return;
            }

            $('#sc-gls-shop-error').hide();
            isFetching = true;
            $.getJSON('/gls/index/getglslist', {
                street: address.street,
                zipcode: address.postcode,
                country: 'DK',
                amount: 20
            })
                .done(function (resp) {
                    renderShops(resp.success ? resp.shops : []);
                })
                .always(function () {
                    isFetching = false;
                });
        }

        function renderShops(shops) {
            availableShops = shops || [];
            const select = $('#sc-gls-shop-select').empty();
            $('#sc-gls-shop-container').show();

            if (!availableShops.length) {
                select.append($('<option value=""/>').text('Ingen pakkeshops fundet'));
                return;
            }
            availableShops.forEach(function (shop) {
                select.append($('<option/>').val(shop.id).text(
                    shop.name + ', ' + shop.street + ' – ' + shop.city + ' (' + shop.distance + ' m)'
                ));
            });

            // Restore an earlier choice, which can only be set once the list exists.
            if (selectedShopId && availableShops.some(function (s) { return String(s.id) === String(selectedShopId); })) {
                select.val(selectedShopId);
            }

            // The browser shows the first shop as selected without firing "change", so save
            // what is actually displayed; otherwise the cart has no shop and the order fails.
            const shown = select.val() || '';
            if (shown && shown !== selectedShopId) {
                selectedShopId = shown;
                saveSelection();
            } else {
                selectedShopId = shown;
            }
        }

        $(document).on('change', '#sc-gls-shop-select', function () {
            selectedShopId = $(this).val() || '';
            saveSelection();
        });

        /* Save */
        function saveSelection() {
            const method = selectedMethod();
            if (!method || method.indexOf('gls_') !== 0) return;

            const isShop = method === 'gls_shop';
            const shopId = $('#sc-gls-shop-select').val() || '';
            // An empty shop would arrive after (and wipe) the real one; the list saves once loaded.
            if (isShop && !shopId) return;

            const shop = isShop
                ? (availableShops.find(function (s) { return String(s.id) === String(shopId); }) || {})
                : {};

            if (saveXhr) {
                saveXhr.abort();
            }
            saveXhr = $.post('/gls/index/save', {
                form_key: $('input[name="form_key"]').val() || window.FORM_KEY || '',
                method: method,
                shop_id: isShop ? shopId : '',
                shop_name: shop.name || '',
                shop_street: shop.street || '',
                shop_postcode: shop.zipcode || '',
                shop_city: shop.city || ''
            })
                .done(function (resp) {
                    if (resp && resp.success) {
                        $(document).trigger('softcode:gls:saved', [method]);
                        if (window.softcode.reloadCart) {
                            window.softcode.reloadCart();
                        }
                    }
                })
                .always(function () {
                    saveXhr = null;
                });
        }

        /* Restore */
        function restoreSelection() {
            $.getJSON('/gls/index/selected', function (resp) {
                if (!resp.success || !resp.method) return;

                $('input[name="delivery_method"][value="' + resp.method + '"]').prop('checked', true);
                if (resp.method === 'gls_shop') {
                    $('#sc-gls-shop-container').show();
                    selectedShopId = resp.shop_id || '';
                    fetchShops();
                }
            });
        }

        function debounce(fn, delay) {
            let timer;
            return function () {
                clearTimeout(timer);
                timer = setTimeout(fn, delay);
            };
        }
    });
});
