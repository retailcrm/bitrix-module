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
            if (response.data.status === 'error' && response.data.errorMsg !== undefined) {
                const errorMsg = 'Ошибка. ' + response.data.errorMsg;
                $('#errorMsg').text(errorMsg);
            }

            if (response.data.status === 'activate') {

            }

            if (response.data.status === 'smsVerification') {
                $('#verificationCodeBlock').show();
            }

        });
}

function sendVerificationCode(){
    const verificationCode =  $('#verificationCode').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.sendVerificationCode',
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

        }
    )
}