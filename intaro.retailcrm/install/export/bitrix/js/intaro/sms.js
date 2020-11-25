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

function resendSms(checkId) {
    BX.ajax.runAction('intaro:retailcrm.api.loyalty.smsverification.resendSms',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                request: checkId
            }
        }
    ).then(
        function(response) {
            $('#checkIdField').val(response.data.checkId);
            initializeClock(id, response.data.endtime)
            console.log(response);
        }
    );
}