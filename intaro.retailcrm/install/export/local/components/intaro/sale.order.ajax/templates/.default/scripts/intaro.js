$(document).ready(function() {

    function isInt(n) {
        return n !== ""
            && !isNaN(n)
            && n.charAt(0) !== "0"
            && Math.round(parseFloat(n)) === parseFloat(n)
            && parseInt(n) > 0;
    }

    function isFloat(n) {
        return n !== ""
            && !isNaN(n)
            && parseFloat(n) > 0
            && (n.split(".").length - 1) === 1
            && Math.round(n) !== n;
    }

    function isErrorsInInputBonuses() {
        let availableBonuses = Number.parseFloat($('#available-bonus-input').val());
        let inputBonuses     = Number.parseFloat($('#bonus-input').val());
        if (inputBonuses > availableBonuses) {
            $('#bonus-input-error')
                .text(window.__MESS__.YOU_CANT_SPEND_MORE + ' ' + availableBonuses + ' ' + window.__MESS__.BONUSES);
            return true;
        } else {
            $('#bonus-input-error').html(null);
        }

        return false;
    }

    function makeAjaxRequest() {
        $('#bonus-msg').html(window.__MESS__.COUNT_FOR_WRITE_OFF);
        let basketItemsHidden = window.__BASKET_ITEMS__;
        let inputBonuses = $('#bonus-input').val();

        if (!isInt(inputBonuses) && !isFloat(inputBonuses) && inputBonuses !== "") {
            $('#bonus-input-error').html(window.__MESS__.VALIDATE_BONUS_ERROR);

            return;
        }

        $('#bonus-input').change(isErrorsInInputBonuses());

        let inputBonusesFloat = Number.parseFloat(inputBonuses);

        BX.ajax.runAction('intaro:retailcrm.api.loyalty.order.loyaltyCalculate',
            {
                data: {
                    sessid: BX.bitrix_sessid(),
                    basketItems: basketItemsHidden,
                    inputBonuses: inputBonusesFloat ?? 0
                }
            }
        )

        BX.Sale.OrderAjaxComponent.sendRequest();
    }

    $('#bonus-input').on('keydown', function() {
        $('#bonus-msg').html(window.__MESS__.DATA_PROCESSING);
    });

    $('#bonus-input').on('keydown', _.debounce(makeAjaxRequest, 1000));
});

function sendOrderVerificationCode() {
    const verificationCode = $('#orderVerificationCode').val();
    const orderId          = $('#orderIdVerify').val();
    const checkId          = $('#checkIdVerify').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.order.sendVerificationCode',
        {
            data: {
                sessid: BX.bitrix_sessid(),
                verificationCode: verificationCode,
                orderId: orderId,
                checkId: checkId
            }
        }
    ).then(
        function(response) {
            if (response.data.status === 'error' && response.data.msg !== undefined) {
                const msg = $('#msg');
                msg.text(response.data.msg);
                msg.css('color', response.data.msgColor);
            }

            if (response.data.status === 'success') {
                const msgBlock = $('#orderConfirm');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }
        }
    )
}
