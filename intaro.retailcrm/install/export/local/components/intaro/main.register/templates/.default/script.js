function addTelNumber(customerId) {
    const phone = $('#loyaltyRegPhone').val();
    const card  = $('#loyaltyRegCard').val();
console.log(phone);
console.log(card);

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
        function(data) {

        });
}