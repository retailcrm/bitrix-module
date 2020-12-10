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

function resendRegisterSms(idInLoyalty) {
    BX.ajax.runAction('intaro:retailcrm.api.loyalty.register.resendRegisterSms',
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

function resendOrderSms(orderId) {
    BX.ajax.runAction('intaro:retailcrm.api.loyalty.order.resendOrderSms',
        {
            data: {
                sessid:  BX.bitrix_sessid(),
                orderId: orderId,
            }
        }
    ).then(function(response) {
        /**
 {
  "status": "success",
  "data": {
    "createdAt": {
      "date": "2020-12-10 17:44:44.000000",
      "timezone_type": 3,
      "timezone": "Europe\/Moscow"
    },
    "expiredAt": {
      "date": "2020-12-10 17:49:44.000000",
      "timezone_type": 3,
      "timezone": "Europe\/Moscow"
    },
    "verifiedAt": null,
    "checkId": "a28ffa8d-268e-4f38-89f3-32c35e34a61a",
    "actionType": "confirm_loyalty_charge"
  },
  "errors": []
}
         */

        let resendAvailable = response.data.createdAt.setMinutes(response.data.createdAt.getMinutes() + 1)

        if (response.data !== undefined) {
            $('#checkIdVerify').val(response.data.checkId);
            initializeClock("countdown", resendAvailable.date);
        }
    });
}
