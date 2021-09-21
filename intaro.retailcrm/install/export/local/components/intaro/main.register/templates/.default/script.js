function serializeObject(array) {
    const object = {};
    $.each(array, function() {
        if (object[this.name] !== undefined) {
            if (!object[this.name].push) {
                object[this.name] = [object[this.name]];
            }
            object[this.name].push(this.value || '');
        } else {
            object[this.name] = this.value || '';
        }
    });
    return object;
}

function saveUserLpFields() {
    const formArray  = $('#lpRegFormInputs').serializeArray();
    const formObject = serializeObject(formArray);

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.saveUserLpFields',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                request: formObject
            }
        }
    ).then(
        function(response) {
            if (response.data.result === true) {
                location.reload();
            } else {
                $('#errMsg').text(response.data.msg)
            }
        }
    );
}

//TODO проверить - возможно мертвый метод
function addTelNumber(customerId) {
    const phone = $('#loyaltyRegPhone').val();
    const card  = $('#loyaltyRegCard').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.accountCreate',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                request: {
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
    const verificationCode = $('#smsVerificationCodeField').val();
    const checkId          = $('#checkIdField').val();

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.activateLpBySms',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                code:    verificationCode,
                checkId: checkId
            }
        }
    ).then(
        function(response) {
            if (response.data.status === 'error' && response.data.msg !== undefined) {
                const msg = response.data.msg;
                $('#errMsg').text(msg);
            }

            if (response.data.status === 'activate') {
                const msgBlock = $('#regBody');
                msgBlock.text(response.data.msg);
                msgBlock.css('color', response.data.msgColor);
            }
        }
    )
}

function lpFieldToggle() {
    if ($('#checkbox_UF_REG_IN_PL_INTARO').is(':checked')) {
        $('.lp_toggled_block').css('display', 'table-row');
        $('.lp_agree_checkbox').prop('checked', true);
    } else {
        $('.lp_agree_checkbox').prop('checked', false);
        $('.lp_toggled_block').css('display', 'none');
    }
}
