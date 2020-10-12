$(document).ready(function() {
    let intervalId;

    function makeAjaxRequest() {
        setTimeout(function() {
            $('#bonus-msg').html('Сколько бонусов потратить?');
            BX.Sale.OrderAjaxComponent.sendRequest();
        }, 1005);
    }

    $('#bonus-input').on('keydown', function() {
            $('#bonus-msg').html('Обработка информации');
            clearInterval(intervalId);
        })

    $('#bonus-input').keyup(function() {
        let availableBonuses = Number.parseInt($('#available-bonus-input').val());
        let inputBonuses = Number.parseInt($('#bonus-input').val());
        if (inputBonuses > availableBonuses) {
            $('#bonus-input-error').text('Вы не можете потратить более ' + availableBonuses + ' бонусов');
        }else{
            $('#bonus-input-error').html(null);
        }
    });

    $('#bonus-input').on('keydown',
        _.debounce(makeAjaxRequest, 1000));
});
