$(document).ready(function() {
    let intervalId;

    function make_ajax_request(e) {
        setTimeout(function() {
            BX.Sale.OrderAjaxComponent.sendRequest();
            console.log('test');
        }, 2000);
    }

    $('#bonus-input')
        .on('keydown', function() {
            clearInterval(intervalId);
        })

    $('#bonus-input').on('keydown',
        _.debounce(make_ajax_request, 1300));
});