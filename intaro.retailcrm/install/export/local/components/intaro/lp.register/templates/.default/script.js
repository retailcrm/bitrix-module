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

function resetUserLpFields() {
    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.resetUserLpFields').then(
        function(response) {
            if (response.data.result === true) {
                location.reload();
            } else {
                $('#errMsg').text(response.data.msg)
            }
        }
    );
}

function activateAccount() {
    let checkboxes = [];
    let numbers = [];
    let strings = [];
    let dates = [];
    let options = [];
    let form = $('#lpRegFormInputs');

    let emailViolation = false;

    form.find(':input[type="email"]')
        .each(
            (index, value) => {
                if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(value.value) !== true) {
                    emailViolation = true;
                }
            }
        )

    if (emailViolation) {
        $('#errMsg').text('Проверьте правильность заполнения email')

        return;
    }

    form.find(':checkbox')
        .each(
            (index, value) => {
                checkboxes[index] = {
                    'code':  value.name,
                    'value': value.checked,
                }
            }
        )

    form.find(':input[type="number"]')
        .each(
            (index, value) => {
                numbers[index] = {
                    'code':  value.name,
                    'value': value.value,
                }
            }
        )

    form.find(':input[type="string"], :input[type="text"], :input[type="email"], textarea')
        .each(
            (index, value) => {
                strings[index] = {
                    'code':  value.name,
                    'value': value.value,
                }
            }
        )

    form.find(':input[type="date"]')
        .each(
            (index, value) => {
                dates[index] = {
                    'code':  value.name,
                    'value': value.value,
                }
            }
        )

    form.find('select')
        .each(
            (index, value) => {
                options[index] = {
                    'code':  value.name,
                    'value': value.value,
                }
            }
        )

    let formObject = {
        checkboxes: checkboxes,
        numbers:    numbers,
        strings:    strings,
        dates:      dates,
        options:    options
    }

    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.activateAccount',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                allFields: formObject
            }
        }
    ).then(
        function(response) {
            if (response.data.status === 'activate') {
                location.reload();
            } else {
                $('#errMsg').text(response.data.msg)
            }
        }
    );
}

//TODO проверить - возможно, это мертвый метод
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
                verificationCode:    verificationCode,
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

/** Управляет отображением блока с полями программы лояльности на странице регистрации. */
function lpFieldToggle() {
    let phone = $('#personalPhone');
    if ($('#checkbox_UF_REG_IN_PL_INTARO').is(':checked')) {
        $('.lp_toggled_block').css('display', 'table-row');
        $('.lp_agree_checkbox').prop('checked', true);
        phone.prop('type', 'tel');
        phone.attr('name', 'REGISTER[PERSONAL_PHONE]');
    } else {
        phone.removeAttr('name');
        phone.prop('type', 'hidden');
        $('.lp_agree_checkbox').prop('checked', false);
        $('.lp_toggled_block').css('display', 'none');
    }
}
