function addTelNumber(customerId) {
    const phone = $('#loyaltyRegPhone').val();
    const card  = $('#loyaltyRegCard').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.accountCreate',
        {
            data: {
                sessid:         BX.bitrix_sessid(),
                loyaltyAccount: {
                    phone:      phone,
                    card:       card,
                    customerId: customerId
                }
            }
        }
    ).then(
        function(response) {
            if (response.data.status === 'error' && response.data.msg !== undefined) {
                const msgBlock = $('#msg');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }

            if (response.data.status === 'activate') {
                const msgBlock = $('#regbody');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }

            if (response.data.status === 'smsVerification') {
                $('#verificationCodeBlock').show();
            }
        });
}

function sendVerificationCode() {
    const verificationCode = $('#verificationCode').val();

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
                $('#msg').text(response.data.msg);
            }

            if (response.data.status === 'activate') {
                const msgBlock = $('#regbody');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }
        }
    )
}
