<?php

require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/baseclienttrait.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/customerstrait.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/customerscorporatetrait.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/loyaltytrait.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/ordertrait.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/traits/carttrait.php');
require_once($this->INSTALL_PATH . '/../classes/general/Http/Client.php');
require_once($this->INSTALL_PATH . '/../classes/general/Response/ApiResponse.php');
require_once($this->INSTALL_PATH . '/../classes/general/RCrmActions.php');
require_once($this->INSTALL_PATH . '/../classes/general/user/RetailCrmUser.php');
require_once($this->INSTALL_PATH . '/../classes/general/events/RetailCrmEvent.php');
require_once $this->INSTALL_PATH . '/../classes/general/RetailcrmConfigProvider.php';
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/offerparam.php');
require_once($this->INSTALL_PATH . '/../lib/icml/settingsservice.php');
require_once($this->INSTALL_PATH . '/../lib/component/agent.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/selectparams.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/unit.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmlcategory.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmldata.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmloffer.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmlsetup.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmlsetupprops.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/xml/xmlsetuppropscategories.php');
require_once($this->INSTALL_PATH . '/../lib/icml/icmldirector.php');
require_once($this->INSTALL_PATH . '/../lib/icml/icmlwriter.php');
require_once($this->INSTALL_PATH . '/../lib/icml/queryparamsmolder.php');
require_once($this->INSTALL_PATH . '/../lib/icml/xmlcategorydirector.php');
require_once($this->INSTALL_PATH . '/../lib/icml/xmlcategoryfactory.php');
require_once($this->INSTALL_PATH . '/../lib/icml/xmlofferdirector.php');
require_once($this->INSTALL_PATH . '/../lib/icml/xmlofferbuilder.php');
require_once($this->INSTALL_PATH . '/../lib/repository/catalogrepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/filerepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/hlrepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/measurerepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/siterepository.php');
require_once($this->INSTALL_PATH . '/../lib/service/hl.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/orm/catalogiblockinfo.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/orm/iblockcatalog.php');
require_once($this->INSTALL_PATH . '/../classes/general/Exception/InvalidJsonException.php');
require_once($this->INSTALL_PATH . '/../classes/general/Exception/CurlException.php');
require_once($this->INSTALL_PATH . '/../classes/general/RestNormalizer.php');
require_once($this->INSTALL_PATH . '/../classes/general/Logger.php');
require_once($this->INSTALL_PATH . '/../classes/general/services/RetailCrmService.php');
require_once($this->INSTALL_PATH . '/../lib/component/constants.php');
require_once($this->INSTALL_PATH . '/../classes/general/ApiClient_v5.php');
require_once($this->INSTALL_PATH . '/../classes/general/order/RetailCrmOrder_v5.php');
require_once($this->INSTALL_PATH . '/../classes/general/history/RetailCrmHistory_v5.php');
require_once($this->INSTALL_PATH . '/../classes/general/cart/RetailCrmCart_v5.php');
require_once($this->INSTALL_PATH . '/../lib/service/managerservice.php');
require_once($this->INSTALL_PATH . '/../lib/service/loyaltyservice.php');
require_once($this->INSTALL_PATH . '/../lib/service/loyaltyaccountservice.php');
require_once($this->INSTALL_PATH . '/../lib/repository/managerrepository.php');
require_once($this->INSTALL_PATH . '/../classes/general/services/BitrixOrderService.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/abstractmodelproxy.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/orderprops.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/tomodule.php');
require_once($this->INSTALL_PATH . '/../lib/repository/abstractrepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/orderpropsrepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/persontyperepository.php');
require_once($this->INSTALL_PATH . '/../lib/repository/tomodulerepository.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/orm/tomodule.php');
require_once($this->INSTALL_PATH . '/../lib/model/bitrix/agreement.php');
require_once($this->INSTALL_PATH . '/../lib/repository/agreementrepository.php');
require_once($this->INSTALL_PATH . '/../lib/service/orderloyaltydataservice.php');
require_once($this->INSTALL_PATH . '/../lib/service/currencyservice.php');
require_once($this->INSTALL_PATH . '/../lib/component/factory/clientfactory.php');
require_once($this->INSTALL_PATH . '/../lib/component/apiclient/clientadapter.php');
require_once($this->INSTALL_PATH . '/../lib/component/advanced/loyaltyinstaller.php');
