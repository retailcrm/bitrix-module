<?php

function update_5_5_0()
{
    if (!class_exists('COption')) {
        return;
    }

    $mid    = 'intaro.retailcrm';
    $option = 'send_payment_amount';

    if (COption::GetOptionString($mid, $option, 'N') !== 'Y') {
        COption::SetOptionString($mid, $option, 'Y');
    }
}
