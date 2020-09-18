<?php

function update_5_5_0()
{
    if (!RetailcrmConfigProvider::shouldSendPaymentAmount()) {
        RetailcrmConfigProvider::setSendPaymentAmount('Y');
    }
}
