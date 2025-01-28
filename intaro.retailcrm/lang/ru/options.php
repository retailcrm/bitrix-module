<?php

$MESS ['ICRM_OPTIONS_GENERAL_TAB'] = 'Общие настройки';
$MESS ['ICRM_OPTIONS_IMPORT_TAB'] = 'Настройки импорта';
$MESS ['ICRM_OPTIONS_ORDER_PROPS_TAB'] = 'Cоответствия полей свойств заказа';
$MESS ['ICRM_CONN_SETTINGS'] = 'Настройка соединения';
$MESS ['ICRM_API_HOST'] = 'Адрес RetailCRM:';
$MESS ['ICRM_API_KEY'] = 'Ключ авторизации:';
$MESS ['ICRM_SITES'] = 'Активные сайты';

$MESS ['ICRM_OPTIONS_GENERAL_CAPTION'] = 'Настройка подключения к системе RetailCRM';
$MESS ['ICRM_OPTIONS_CATALOG_CAPTION'] = 'Сопоставление справочников системы RetailCRM и Bitrix';
$MESS ['ICRM_OPTIONS_ORDER_PROPS_CAPTION'] = 'Сопоставление полей заказа системы RetailCRM и Bitrix';
$MESS ['ICRM_OPTIONS_LOYALTY_PROGRAM_CAPTION'] = 'Настройка работы с программой лояльности';
$MESS ['ICRM_OPTIONS_OTHER_CAPTION'] = 'Настройка дополнительных опций интеграции c системой RetailCRM';


$MESS ['ICRM_OPTIONS_CATALOG_TAB'] = 'Настройка справочников';
$MESS ['DELIVERY_TYPES_LIST'] = 'Способы доставки';
$MESS ['PAYMENT_TYPES_LIST'] = 'Способы оплаты';
$MESS ['PAYMENT_STATUS_LIST'] = 'Статусы';
$MESS ['ORDER_TYPES_LIST'] = 'Типы заказа';
$MESS ['CRM_ORDER_METHODS'] = 'Передача заказов из CRM в Битрикс';
$MESS ['LP_WARNING'] = 'Программа лояльности RetailCRM доступна только при активной опции "Выгрузка заказов по событию"';
$MESS ['CRM_ORDER_METHODS_OPTION'] = 'Выгружать из RetailCRM заказы оформленные выбранными способами';
$MESS ['CONTRAGENTS_TYPES_LIST'] = 'Тип контрагента';
$MESS ['PAYMENT_LIST'] = 'Оплата';
$MESS ['PAYMENT_Y'] = 'Оплачен';
$MESS ['PAYMENT_N'] = 'Не оплачен';
$MESS ['LEGAL_DETAIL'] = 'Юридические и банковские реквизиты';
$MESS ['ORDER_CUSTOM'] = 'Кастомные поля';
$MESS ['COUPON_CUSTOM_FIELD'] = 'Выберите пользовательское поле в CRM для передачи примененного купона в заказе Битрикс';
$MESS ['SELECT_VALUE'] =  '-- Выберите значение --';
$MESS ['ORDER_UPLOAD'] = 'Повторная выгрузка заказов';
$MESS ['ORDER_NUMBER'] = 'Номера заказов: ';
$MESS ['ORDER_UPLOAD_INFO'] = 'Для загрузки всех заказов нажмите кнопку «Начать выгрузку». Или перечислите необходимые ID заказов через запятую, интервалы через тире. Например: 1, 3, 5-10, 12, 13... и т.д.';
$MESS ['INTEGRATION_PAYMENT_LIST'] = 'Для интеграционных оплат статус не передаётся';
$MESS ['INTEGRATIONS'] = ' (интеграционная)';

$MESS ['ERR_COUNT_SITES'] = 'Введенный вами API Ключ относится более чем к одному магазину.
Измените настройки доступа для API ключа, он должен работать только с одним магазином в CRM';
$MESS ['ERR_CURRENCY_SITES'] = 'Валюта сайта отличается от валюты магазина в CRM.
Для корректной работы интеграции, валюты в CRM и CMS должны совпадать';
$MESS ['ERR_CMS_CURRENCY'] = 'Не удалось получить валюту сайта Bitrix';
$MESS ['ERR_CRM_CURRENCY'] = 'Не удалось получить валюту магазина CRM';
$MESS ['CRM_STORE'] = 'CRM магазин:  ';

$MESS ['ICRM_OPTIONS_SUBMIT_TITLE'] = 'Сохранить настройки';
$MESS ['ICRM_OPTIONS_SUBMIT_VALUE'] = 'Сохранить';

$MESS ['ERR_403_LABEL'] = 'Для корректной работы модуля необходимо добавить: %s';
$MESS ['ERR_404'] = 'Возможно не верно введен адрес CRM.';
$MESS ['ERR_403'] = '<a target="_blank" href="https://docs.retailcrm.ru/Users/Integration/SiteModules/1CBitrix/CreatingOnlineStore1CBitrix">Недостаточно прав для API ключа. %s</a>';
$MESS ['ERR_403_CUSTOM'] = 'Недостаточно прав для API ключа!';
$MESS ['ERR_JSON'] = 'Получены некорректные данные из CRM, проверьте данные справочников в настройках';
$MESS ['ERR_0'] = 'Превышено время ожидания ответа от сервера.';
$MESS ['ICRM_OPTIONS_OK'] = 'Изменения успешно сохранены.';
$MESS ['CANCELED'] = 'Является флагом «Отменен»';
$MESS ['STATUS_NOT_SETTINGS'] ='Не найдены подходящие статусы в Битрикс';
$MESS ['INFO_1'] = ' Задайте соответствие между справочниками 1C-Битрикс и справочниками RetailCRM.';

$MESS ['ICRM_OPTIONS_ORDER_DISCHARGE_TAB'] = 'Режим выгрузки заказов';
$MESS ['ORDER_DISCH'] = 'Режим выгрузки заказов';
$MESS ['DISCHARGE_AGENT'] = 'Выгрузка заказов с помощью агента';
$MESS ['DISCHARGE_EVENTS'] = 'Выгрузка заказов по событию';
$MESS ['DISCHARGE_WITHOUT_UPDATE'] = 'Выгрузка заказов по агенту (только создание заказов)';
$MESS ['INFO_2'] = ' Задайте соответствие между полями заказа 1C-Битрикс и RetailCRM.';

$MESS ['ORDER_PROPS'] = 'Настройки соответствия полей заказа RetailCRM свойствам заказа 1С-Битрикс';
$MESS ['FIO'] = 'Ф.И.О.';
$MESS ['ZIP'] = 'Индекс';
$MESS ['ADDRESS'] = 'Адрес (строкой)';
$MESS ['PHONE'] = 'Телефон';
$MESS ['EMAIL'] = 'E-mail';
$MESS ['COUNTRY'] = 'Страна';
$MESS ['REGION'] = 'Область / Край';
$MESS ['CITY'] = 'Город';
$MESS ['STREET'] = 'Улица';
$MESS ['BUILDING'] = 'Строение';
$MESS ['FLAT'] = 'Квартира';
$MESS ['INTERCOMCODE'] = 'Домофон';
$MESS ['FLOOR'] = 'Этаж';
$MESS ['BLOCK'] = 'Подъезд';
$MESS ['HOUSE'] = 'Строение / корпус';
$MESS ['ADDRESS_SHORT'] = 'Краткий адрес';
$MESS ['ADDRESS_FULL'] = 'Детальный адрес';

$MESS ['UPDATE_DELIVERY_SERVICES'] = 'Выгрузка служб доставок';

$MESS ['MESS_1'] = 'Произошла ошибка при выгрузке одной или нескольких служб доставок, попробуйте еще раз. Если проблема повторилась, обратитесь в Интаро Софт.';
$MESS ['MESS_2'] = 'Произошла ошибка сервера, обратитесь в Интаро Софт.';

$MESS ['ORDER_TYPES_LIST_CUSTOM'] = 'Внимание! Используется не стандартное соответвие типов заказов.';
$MESS ['ORDER_UPL_START'] = 'Начать выгрузку';

$MESS ['UPLOAD_ORDERS_OPTIONS'] = 'Ручная выгрузка';
$MESS ['LOYALTY_PROGRAM_TITLE'] = 'Программа лояльности';
$MESS ['LOYALTY_PROGRAM_TOGGLE_MSG'] = 'Включить программу лояльности';
$MESS ['OTHER_OPTIONS'] = 'Прочие настройки';
$MESS ['ORDERS_OPTIONS'] = 'Настройки заказов';
$MESS ['ORDER_NUMBERS'] = 'Транслировать номера заказов созданных в CRM в магазин';
$MESS ['ORDER_VAT'] = 'Передавать НДС товаров';

$MESS ['CRM_API_VERSION'] = 'Версия API клиента';
$MESS ['CURRENCY'] = 'Валюта, устанавливаемая в заказе при выгрузке из CRM';
$MESS ['ORDER_DIMENSIONS'] = 'Передавать габариты и вес товаров в заказе';
$MESS ['SEND_PICKUP_POINT_ADDRESS'] = 'Передавать пункт самовывоза';
$MESS ['SEND_PICKUP_POINT_ADDRESS_WARNING'] = 'Важно! Адрес пункта самовывоза корректно передается при одной добавленной отгрузке в заказе 1C-Bitrix. Если у вас добавлено более одной отгрузки, будет передана только первая.';
$MESS ['SEND_PAYMENT_AMOUNT'] = 'Передавать сумму оплаты в заказе';

$MESS ['INVENTORIES_UPLOAD'] = 'Включить выгрузку остатков в разрезе складов';
$MESS ['INVENTORIES'] = 'Склады';
$MESS ['SHOPS_INVENTORIES_UPLOAD'] = 'Магазины в которые будут грузиться остатки';
$MESS ['IBLOCKS_UPLOAD'] = 'Инфоблоки товаров';

$MESS ['PRICES_UPLOAD'] = 'Включить выгрузку типов цен для товаров';
$MESS ['PRICE_TYPES'] = 'Выгружаемые типы цен';
$MESS ['SHOPS_PRICES_UPLOAD'] = 'Магазины в которые будут грузиться дополнительные типы цен';

$MESS ['DEMON_COLLECTOR'] = 'Активировать Демон Collector';
$MESS ['DEMON_KEY'] = 'Ключ для';

$MESS ['ONLINE_CONSULTANT'] = 'Активировать Онлайн-консультанта';
$MESS ['ONLINE_CONSULTANT_LABEL'] = 'Скрипт для Онлайн-консультанта';

$MESS ['UNIVERSAL_ANALYTICS'] = 'Включить интеграцию с UA';
$MESS ['ID_UA'] = 'Идентификатор отслеживания:';
$MESS ['INDEX_UA'] = 'Индекс пользовательского параметра:';

$MESS ['CART'] = 'Передавать состав корзины в систему';
$MESS ['CART_DESCRIPTION'] = 'При включенной опции, данные о составе корзины передаются в карточку клиента в системе';

$MESS ['API_NOT_FOUND'] = 'Неверная версия API';
$MESS ['API_NOT_WORK'] = 'Выбранная версия API не поддерживается';

$MESS['CORP_CLIENTE'] = 'Корпоративный клиент';
$MESS['CORP_NAME'] = "Наименование";
$MESS['CORP_ADRESS'] = "Адрес";
$MESS['CORP_LABEL'] = "Магазины в которые будут грузиться корпоративные клиенты";

$MESS['ROUND_LABEL'] = "При включенной опции округление будет происходить в меньшую сторону";
$MESS['ROUND_HEADER'] = "При включенной опции округление будет происходить в меньшую сторону";
$MESS['ROUND_PRICE_FOR_SAME_POSITIONS'] = "Округление цены товара при сборе одинаковых товарных позиций";

$MESS['PURCHASE_ICML'] = "При включенной опции в генерации icml будет добавлен сброс закупочной цены на 0 если она не указана";
$MESS['PURCHASE_HEADER'] = "Сброс закупочной цены в icml";
$MESS['PHONE_REQUIRED'] = "В настройках главного модуля была включена опция «Номер телефона является обязательным», что может вызвать проблемы с обратной синхронизацией. Для корректной работы необходимо отключить данную опцию.";
$MESS['CHANGE_SHIPMENT_STATUS_FROM_CRM'] = "Изменять статус отгрузки при получении соответствующего флага из RetailCRM";
$MESS ['LOYALTY_PROGRAM_TITLE'] = 'Программа лояльности';
$MESS ['LOYALTY_PROGRAM_TOGGLE_MSG'] = 'Активность программы лояльности';
$MESS ['LP_CUSTOM_TEMP_CREATE_MSG'] = 'Создать шаблон default_loyalty для компонента оформления заказа sale.order.ajax c функциями Программы лояльности. <br> <b>Внимение:</b> если шаблон уже существует, то он будет перезаписан';
$MESS ['LP_DEF_TEMP_CREATE_MSG'] = 'Заменить шаблон .default компонента sale.order.ajax шаблоном с функциями Программы лояльности. <br> Если в папке .local уже есть шаблон .default для sale.order.ajax, то он будет скопирован в папку .default_backup';

$MESS ['LOYALTY_PROGRAM_TITLE'] = 'Программа лояльности';
$MESS ['LOYALTY_PROGRAM_TOGGLE_MSG'] = 'Активность программы лояльности';
$MESS ['LP_CUSTOM_TEMP_CREATE_MSG'] = 'Создать шаблон intaro.retailCRM для компонента оформления заказа sale.order.ajax c функциями Программы лояльности. <br> <b>Внимение:</b> если шаблон уже существует, то он будет перезаписан';
$MESS ['LP_DEF_TEMP_CREATE_MSG'] = 'Заменить шаблон .default компонента sale.order.ajax шаблоном с функциями Программы лояльности. <br> Если в папке .local уже есть шаблон .default для sale.order.ajax, то он будет скопирован в папку .default_backup';
$MESS ['LP_CUSTOM_TEMP_CREATE_MSG'] = 'Создать шаблон default_loyalty для компонента оформления заказа sale.order.ajax c функциями Программы лояльности. <br> <b>Внимание:</b> если шаблон уже существует, то он будет перезаписан';
$MESS ['LP_CUSTOM_REG_TEMP_CREATE_MSG'] = 'Создать шаблон default_loyalty для компонента регистрации main.register c функциями Программы лояльности. <br> <b>Внимание:</b> если шаблон уже существует, то он будет перезаписан';
$MESS ['LP_DEF_TEMP_CREATE_MSG'] = 'Заменить шаблон .default компонента sale.order.ajax шаблоном с функциями Программы лояльности. <br>  Если в папке шаблонов компонента уже будет .default, то он будет скопирован в папку .default_backup';
$MESS ['LP_CUSTOM_TEMP_CREATE_MSG'] = 'Создать шаблон default_loyalty для компонента регистрации %s c функциями Программы лояльности. <br> <b>Внимание:</b> если шаблон уже существует, то он будет перезаписан';
$MESS ['LP_DEF_TEMP_CREATE_MSG'] = 'Заменить шаблон .default компонента %s шаблоном с функциями Программы лояльности. <br>  Если в папке шаблонов компонента уже будет .default, то он будет скопирован в папку .default_backup';
$MESS ['LP_CREATE_TEMPLATE'] = 'Создать шаблон';
$MESS ['LP_REPLACE_TEMPLATE'] = 'Заменить шаблон';
$MESS ['LP_SALE_ORDER_AJAX_HEAD'] = ' Управление компонентом Оформление заказа (sale.order.ajax)';
$MESS ['LP_TEMP_CHOICE_MSG'] = 'Выберите, в каких шаблонах сайта будет доступен шаблон компонента с функциями Программы лояльности:';
$MESS ['CREATING_AN_ADDITIONAL_TEMPLATE'] = 'Создание дополнительного шаблона';
$MESS ['REPLACING_THE_STANDARD_TEMPLATE'] = 'Замена стандартного шаблона .default';

$MESS ['LP_MAIN_REGISTER_HEAD'] = 'Управление компонентом регистрации (main.register)';
$MESS ['LP_MAIN_BASKET_HEAD'] = 'Управление компонентом корзины (sale.basket.basket)';

$MESS ['AGREEMENT_PROCESSING_PERSONAL_DATA'] = 'Соглашение на обработку персональных данных';
$MESS ['ACCEPTANCE_TERMS_LOYALTY_PROGRAM'] = 'Согласие с условиями программы лояльности';
$MESS ['EDITING_AGREEMENTS'] = 'Редактирование соглашений';
$MESS ['LOYALTY_PROGRAM_ACTIVATED'] = 'Программа лояльности активирована';
$MESS ['LOYALTY_PROGRAM_DEACTIVATED'] = 'Программа лояльности деактивирована';

$MESS ['TEMPLATE_SUCCESS_COPING'] = 'Шаблон успешно скопирован';
$MESS ['TEMPLATES_SUCCESS_COPING'] = 'Шаблоны успешно скопированы';
$MESS ['TEMPLATES_COPING_ERROR'] = 'Ошибка копирования шаблонов';
$MESS ['TEMPLATE_COPING_ERROR'] = 'Ошибка копирования шаблона';

$MESS ['ACTIVITY_SETTINGS'] = 'Настройки активности модуля';
$MESS ['DEACTIVATE_MODULE'] = 'Деактивировать модуль';

$MESS ['WRONG_CREDENTIALS'] = 'Введите адрес и ключ авторизации CRM системы';
$MESS ['Wrong "apiKey" value.'] = 'Недействительный ключ авторизации';

$MESS ['ORDER_TRACK_NUMBER'] = 'Получать трек-номер';

$MESS ['CUSTOM_FIELDS_TITLE'] = 'Пользовательские поля';
$MESS ['CUSTOM_FIELDS_CAPTION'] = 'Сопоставление пользовательских полей';
$MESS ['CUSTOM_FIELDS_TOGGLE_MSG'] = 'Активировать синхронизацию пользовательских полей';
$MESS ['CUSTOM_FIELDS_ORDER_LABEL'] = 'Пользовательские поля заказа';
$MESS ['CUSTOM_FIELDS_USER_LABEL'] = 'Пользовательские поля клиента';
$MESS ['INTEGER_TYPE'] = 'Целое число';
$MESS ['STRING_TYPE'] = 'Строка/Текст';
$MESS ['NUMERIC_TYPE'] = 'Число';
$MESS ['BOOLEAN_TYPE'] = 'Флажок (да/нет)';
$MESS ['DATE_TYPE'] = 'Дата';
$MESS ['NOTATION_CUSTOM_FIELDS'] = 'Перед подключением данного функционала, убедитесь, что у вас нет кастомизированных файлов по работе с заказами и клиентами, связанных со старыми версиями модуля.';
$MESS ['NOTATION_MATCHED_CUSTOM_FIELDS'] = 'Для корректного обмена данными типы сопоставляемых полей должны быть одинаковыми!';
$MESS ['ADD_LABEL'] = 'Добавить';
$MESS ['DELETE_MATCHED'] = 'Удалить';

$MESS ['LOCATION_LABEL'] = 'Местоположение (LOCATION)';
$MESS ['TEXT_ADDRESS_LABEL'] = 'Адрес (строкой)';

$MESS ['SYNC_INTEGRATION_PAYMENT'] = 'Активировать передачу статусов интеграционных оплат';
$MESS ['DESCRIPTION_AUTO_PAYMENT_TYPE'] = 'Автоматически созданный тип оплаты для подмены интеграционной (Bitrix)';
$MESS ['NO_INTEGRATION_PAYMENT'] = '(Не интеграционная)';
$MESS ['ERR_CHECK_JOURNAL'] = 'Ошибка при сохранении. Подробности в журнале событий';
$MESS ['ERROR_LINK_INTEGRATION_PAYMENT'] = 'Ошибка при сопоставлении интеграционных оплат';
$MESS ['ERROR_UPDATE_PAYMENT_TYPES_DELIVERY'] = 'Ошибка при обновлении способов оплаты для доставок';
$MESS ['INTEGRATION_PAYMENT_LABEL'] = 'При сопоставлении интеграционных оплат CRM, на стороне системы создаётся обычная оплата, к которой будут привязываться заказы. <br> Если в вашей CRM используются интеграционные доставки, новый способ оплаты необходимо вручную активировать в настройках интеграций.';
$MESS ['NEED_PERMISSIONS_REFERENCE_LABEL'] = 'Для корректной работы опции апи-ключу необходимы доступы на получение и редактирование справочников';

$MESS ['FIX_UPLOAD_CUSTOMER_HEADER'] = 'Исправление даты регистрации клиентов в CRM';
$MESS ['FIX_UPLOAD_CUSTOMER_BUTTON_LABEL'] = 'Исправить дату регистрации клиентов в CRM';
$MESS ['FIX_UPLOAD_CUSTOMER_INFO'] = 'При нажатии на эту кнопку будет создан агент для запуска скрипта. Обратите внимание, что время выполнения скрипта может варьироваться в зависимости от количества клиентов в базе данных. Для минимизации возможных нарушений в работе скрипта рекомендуется запускать его в ночное время. Этот скрипт может быть запущен только один раз.';
$MESS ['FIX_UPLOAD_CUSTOMER_AFTER_SUBMIT'] = 'Агент создан и запустится в ближайшее время';
$MESS ['FIX_UPLOAD_CUSTOMER_AFTER_SUBMIT_ERROR'] = 'Возникла ошибка при добавлении агента';
