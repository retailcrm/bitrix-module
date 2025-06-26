function startTrack(...trackerEvents) {
    BX.ready(function () {
        if (trackerEvents.includes('open_cart')) {
            sendCartView();
        }

        if (trackerEvents.includes('cart')) {
            sendCartChange();
        }
    });
}

function sendCartView() {
    if (BX && BX.Sale && BX.Sale.BasketComponent) {
        BX.ajax({
            url: '/local/ajax/ajaxBasket.php',
            method: 'POST',
            dataType: 'json',
            data: {
                sessid: BX.bitrix_sessid(),
                event: 'open_cart'
            },
            onsuccess: function (result) {
                if (result.success) {
                    setTimeout(function() {
                        ocapi.setCustomerSiteId(result.userId);
                        ocapi.event('open_cart', {customer_email: result.email});
                    }, 3000);
                }
            },
            onfailure: function () {
                return;
            }
        });
    }
}

function sendCartChange() {
    BX.addCustomEvent('onBasketChange', function(data) {
        BX.ajax({
            url: '/local/ajax/ajaxBasket.php',
            method: 'POST',
            dataType: 'json',
            data: {
                sessid: BX.bitrix_sessid(),
                event: 'cart'
            },
            onsuccess: function (result) {
                if (result.success) {
                    let cartObject = {};
                    cartObject.items = [];

                    result.items.forEach(function(item) {
                        cartObject.items.push(
                            {
                                external_id: String(item.offer_id) ?? String(item.product_id),
                                price: item.price,
                                quantity: item.quantity,
                                xmlid: String(item.xml_id) ?? ''
                            }
                        );
                    });

                    setTimeout(function() {
                        ocapi.setCustomerSiteId(result.userId);
                        ocapi.event('cart', cartObject);
                    }, 3000);
                }
            },
            onfailure: function () {
                return;
            }
        });
    });
}
