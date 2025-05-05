function startTrack(...trackerEvents) {
    BX.ready(function () {
        if (trackerEvents.includes("open_cart")) {
            sendCartView();
	    }

        if (trackerEvents.includes("cart")) {
            sendCartChange();
	    }
    });
}

function sendCartView() {
    if (BX && BX.Sale && BX.Sale.BasketComponent) {
        if (window.basketRequestSent) return;

        window.basketRequestSent = true;

        BX.ajax({
            url: '/local/ajax/ajaxBasket.php',
            method: 'POST',
            dataType: 'json',
            timeout: 10,
            data: {
                sessid: BX.bitrix_sessid(),
                event: "cartView"
            },
            onsuccess: function (result) {
                if (result.success) {
                    setTimeout(function() {
                        ocapi.event("open_cart", {customer_email: result.email});
                    }, 3000);
                } else {
                    console.warn("Ошибка получения email: " + result.message);
                }
            },
            onfailure: function () {
                console.error("Ajax-запрос не выполнен");
            }
        });
    }
}

function sendCartChange() {
    BX.addCustomEvent('onBasketChange', function(data) {
        if (window.changeRequestSent) return;

        window.changeRequestSent = true;
        
        BX.ajax({
            url: '/local/ajax/ajaxBasket.php',
            method: 'POST',
            dataType: 'json',
            timeout: 10,
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
                                external_id: String(item.product_id) ?? String(item.id),
                                price: item.price,
                                quantity: item.quantity
                            }
                        );
                    });

                    setTimeout(function() {
			            ocapi.event("cart", cartObject);
                    }, 3000);
                } else {
                    console.warn("Ошибка получения корзины: " + result.message);
                }
            },
            onfailure: function () {
                console.error("Ajax-запрос не выполнен");
            }
        });
    });
}
