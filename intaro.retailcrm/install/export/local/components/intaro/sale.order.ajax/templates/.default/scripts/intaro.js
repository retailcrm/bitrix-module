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
