$(document).ready(function() {
    function makeAjaxRequest() {
        $('#bonus-msg').html('Сколько бонусов потратить?');
        let basketItemsHidden = $('#basket-items-hidden').val();
        let inputBonuses     = Number.parseInt($('#bonus-input').val());

        BX.ajax.runAction('intaro:retailcrm.api.loyalty.order.calculateBonus',
            {
                data: {
                    sessid:            BX.bitrix_sessid(),
                    basketItemsHidden: basketItemsHidden,
                    inputBonuses: inputBonuses
                }
            }
        ).then(
            function(response) {
                if (response.data !== null) {
                    BX.Sale.OrderAjaxComponent.willBeCredited = String(response.data.order.bonusesCreditTotal);
                    BX.Sale.OrderAjaxComponent.bonusCurrency = String(inputBonuses);
                }
            }
        )

        BX.Sale.OrderAjaxComponent.sendRequest();
    }

    $('#bonus-input').on('keydown', function() {
        $('#bonus-msg').html('Обработка информации');
    });

    $('#bonus-input').keyup(function() {
        let availableBonuses = Number.parseInt($('#available-bonus-input').val());
        let inputBonuses     = Number.parseInt($('#bonus-input').val());
        if (inputBonuses > availableBonuses) {
            $('#bonus-input-error').text('Вы не можете потратить более ' + availableBonuses + ' бонусов');
        } else {
            $('#bonus-input-error').html(null);
        }
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
                sessid:           BX.bitrix_sessid(),
                verificationCode: verificationCode,
                orderId:          orderId,
                checkId:          checkId
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
