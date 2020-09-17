<?php

if (!RetailcrmConfigProvider::shouldSendPaymentAmount()) {
    RetailcrmConfigProvider::setSendPaymentAmount('Y');
}
