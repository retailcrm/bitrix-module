$(document).ready(function() {
    function makeAjaxRequest() {
            $('#bonus-msg').html('Сколько бонусов потратить?');
            BX.Sale.OrderAjaxComponent.sendRequest();
    }

    $('#bonus-input').on('keydown', function() {
            $('#bonus-msg').html('Обработка информации');
        });

    $('#bonus-input').keyup(function() {
        let availableBonuses = Number.parseInt($('#available-bonus-input').val());
        let inputBonuses = Number.parseInt($('#bonus-input').val());
        if (inputBonuses > availableBonuses) {
            $('#bonus-input-error').text('Вы не можете потратить более ' + availableBonuses + ' бонусов');
        } else {
            $('#bonus-input-error').html(null);
        }
    });

    $('#bonus-input').on('keydown', _.debounce(makeAjaxRequest, 1000));
});

function sendVerificationCode() {
    const verificationCode = $('#orderVerificationCode').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.sendVerificationCode',
        {
            data: {
                sessid: BX.bitrix_sessid(),
                code:   verificationCode
            }
        }
    ).then(
        function(response) {
            if (response.data.status === 'error' && response.data.msg !== undefined) {
                const msg = response.data.msg;
                $('#msg').text(msg);
            }

            if (response.data.status === 'activate') {
                const msgBlock = $('#orderConfirm');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }
        }
    )
}