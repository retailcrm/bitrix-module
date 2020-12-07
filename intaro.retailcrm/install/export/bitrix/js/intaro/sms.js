function getTimeRemaining(endtime) {
    return Date.parse(endtime) - Date.parse(new Date());
}

function initializeClock(id, endtime) {
    $('#countdownDiv').show();
    $('#deadlineMessage').hide();

    const timeInterval = setInterval(updateClock, 1000);
    const clock        = document.getElementById(id);

    function updateClock() {
        const time = getTimeRemaining(endtime);

        if (time <= 0) {
            $('#countdownDiv').hide();
            $('#deadlineMessage').show();
            clearInterval(timeInterval);
            return true;
        }

        clock.innerText = String(time).slice(0, -3);
    }

    updateClock();
}

function resendSms(idInLoyalty) {
    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.resendSms',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                idInLoyalty: idInLoyalty
            }
        }
    ).then(function(response) {
            $('#lpRegMsg').text(response.data.msg);
            $('#checkIdField').val(response.data.form.fields.checkId.value);
            initializeClock("countdown", response.data.expiredTime);
        });
}
