require(['jquery'], function ($) {
    $(function () {

        /* =================================================
           GLS GUARDS
        ================================================= */
        if (!window.softcode || !window.softcode.delivery) return;
        if (!window.softcode.gls || !window.softcode.gls.enabled) return;

        const mount = $(window.softcode.delivery.mountPoint);
        if (!mount.length) return;

        /* =================================================
           STATE
        ================================================= */
        let isFetching = false;
        let isSaving = false;

        /* =================================================
           RENDER GLS DELIVERY METHODS
        ================================================= */
        $.getJSON('/gls/index/methods', function (resp) {
            if (!resp.success || !resp.methods.length) return;

            const wrapper = $('<div class="sc-gls-wrapper"/>');

            resp.methods.forEach(method => {
                wrapper.append(`
                    <label class="sc-gls-method">
                        <input type="radio"
                               name="delivery_method"
                               value="${method.code}">
                        ${method.label}
                        <strong>(${method.price.toFixed(2)} kr.)</strong>
                    </label><br/>
                `);
            });

            wrapper.append(`
                <div id="sc-gls-shop-container"
                     style="display:none; margin-top:10px">

                    <div id="sc-gls-shop-error"
                         class="text-danger small mb-2"
                         style="display:none">
                        Indtast vejnavn og postnummer for at se pakkeshops
                    </div>

                    <select id="sc-gls-shop-select" style="max-width:350px">
                        <option value="">Indtast adresse og postnr…</option>
                    </select>
                </div>
            `);

            mount.append(wrapper);
            restoreGlsSelection();

            /* ===========================
               DELIVERY METHOD CHANGE
            =========================== */
            $(document).on('change', 'input[name="delivery_method"]', function () {
                if ($(this).val() === 'gls_shop') {
                    $('#sc-gls-shop-container').show();
                    fetchPakkeshops();
                } else {
                    $('#sc-gls-shop-container').hide();
                }
                saveGlsSelection();
            });
        });

        /* =================================================
           WATCH ADDRESS FIELDS (MAIN + ALT)
        ================================================= */
        $(
            '#sc-address-street, #sc-address-postcode,' +
            '#sc-alt-street, #sc-alt-postcode,' +
            '#sc-use-alt-address'
        ).on(
            'input change',
            debounce(function () {
                if ($('input[name="delivery_method"]:checked').val() === 'gls_shop') {
                    fetchPakkeshops();
                }
            }, 400)
        );

        /* =================================================
           GET ACTIVE ADDRESS (MAIN OR ALT)
        ================================================= */
        function getActiveAddress() {
            const useAlt = $('#sc-use-alt-address').is(':checked');

            if (useAlt) {
                return {
                    street: $('#sc-alt-street').val()?.trim(),
                    postcode: $('#sc-alt-postcode').val()?.trim()
                };
            }

            return {
                street: $('#sc-address-street').val()?.trim(),
                postcode: $('#sc-address-postcode').val()?.trim()
            };
        }

        /* =================================================
           FETCH GLS PAKKESHOPS (UX-CORRECT)
        ================================================= */
        function fetchPakkeshops() {
            if (isFetching) return;

            const { street, postcode } = getActiveAddress();

            const selected =
                $('input[name="delivery_method"]:checked').val();

            if (selected !== 'gls_shop') return;

            /* ===========================
               VALIDATION (NO HOUSE NUMBER)
            =========================== */
            if (!street || !postcode || postcode.length < 4) {
                $('#sc-gls-shop-error').show();
                $('#sc-gls-shop-select')
                    .empty()
                    .append('<option value="">Indtast adresse og postnr…</option>');
                return;
            }

            $('#sc-gls-shop-error').hide();
            isFetching = true;

            $.getJSON('/gls/index/getglslist', {
                street: street,
                zipcode: postcode,
                country: 'DK',
                amount: 20
            })
                .done(function (resp) {
                    renderShopList(resp.success ? resp.shops : []);
                })
                .always(function () {
                    isFetching = false;
                });
        }

        /* =================================================
           RENDER SHOP LIST
        ================================================= */
        function renderShopList(shops) {
            const select = $('#sc-gls-shop-select').empty();
            $('#sc-gls-shop-container').show();

            if (!shops || !shops.length) {
                select.append('<option value="">Ingen pakkeshops fundet</option>');
                return;
            }

            shops.forEach(shop => {
                select.append(`
                    <option value="${shop.id}">
                        ${shop.name}, ${shop.street} – ${shop.city}
                        (${shop.distance} m)
                    </option>
                `);
            });
        }

        /* =================================================
           SAVE GLS SELECTION
        ================================================= */
        function saveGlsSelection() {
            if (isSaving) return;

            const method = $('input[name="delivery_method"]:checked').val();
            const shopId = $('#sc-gls-shop-select').val() || '';

            if (!method || !method.startsWith('gls_')) return;

            isSaving = true;

            $.post('/gls/index/save', {
                method: method,
                shop_id: method === 'gls_shop' ? shopId : ''
            })
                .done(function () {
                    if (window.softcode && window.softcode.reloadCart) {
                        window.softcode.reloadCart();
                    }
                })
                .always(function () {
                    isSaving = false;
                });
        }

        $(document).on('change', '#sc-gls-shop-select', function () {
            saveGlsSelection();
        });

        /* =================================================
           RESTORE GLS SELECTION
        ================================================= */
        function restoreGlsSelection() {
            $.getJSON('/gls/index/selected', function (resp) {
                if (!resp.success || !resp.method) return;

                const $radio =
                    $('input[name="delivery_method"][value="' + resp.method + '"]');

                if ($radio.length) {
                    $radio.prop('checked', true);
                }

                if (resp.method === 'gls_shop') {
                    $('#sc-gls-shop-container').show();
                    fetchPakkeshops();

                    if (resp.shop_id) {
                        $('#sc-gls-shop-select').val(resp.shop_id);
                    }
                }
            });
        }

        /* =================================================
           DEBOUNCE
        ================================================= */
        function debounce(fn, delay) {
            let timer;
            return function () {
                clearTimeout(timer);
                timer = setTimeout(fn, delay);
            };
        }

    });
});
