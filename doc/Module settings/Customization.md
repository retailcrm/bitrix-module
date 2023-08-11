### Кастомизация модуля

В модуле доступна кастомизация классов, без потери модифицированного кода при обновлении.

Для этого требуется создать копию необходимого для кастомизации файла и расположить его директории
`bitix/php_inteface/retailcrm`

Имеется возможность кастомизации следующих файлов:
* RestNormalizer.php
* Logger.php
* Client.php
* RCrmActions.php
* RetailCrmUser.php
* RetailCrmICML.php
* RetailCrmInventories.php
* RetailCrmPrices.php
* RetailCrmCollector.php
* RetailCrmUa.php
* RetailCrmEvent.php
* RetailCrmHistory_v4.php
* RetailCrmHistory_v5.php
* RetailCrmOrder_v4.php
* RetailCrmOrder_v5.php
* ApiClient_v4.php  
* ApiClient_v5.php

С версии 6.3.19 доступна кастомизация ICML, файлы которого расположены в модуле по пути `lib/icml`:
* xmlofferbuilder.php
* xmlofferdirector.php
* icmlwriter.php
* queryparamsmolder.php
* settingsservice.php
* xmlcategorydirector.php
* xmlcategoryfactory.php
* icmldirector.php

Для кастомизации файлов, в названии которых есть используемая версия API,
создаются файлы с названием без указания версии, например - `RetailCrmHistory.php`.

После создания копии файла с классом в директории `bitrix/php_interface/retailcrm`
модуль будет использовать кастомизированный класс, можете вносит изменения в его методы.
