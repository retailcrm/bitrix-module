<?php //
//
//namespace RetailCrm;
//
//use RetailCrm\Response\ApiResponse;
//use RetailCrm\Exception\CurlException;
//
///**
// * retailCRM API client class
// */
//class RestApi
//{
//    const VERSION = 'v3';
//    const METHOD_GET = 'GET';
//    const METHOD_POST = 'POST';
//    
//    protected $client;
//    protected $url;
//    protected $defaultParameters;
//    protected $generatedAt;
//    
//    /**
//     * Site code
//     */
//    protected $siteCode;
//    
//    /**
//     * Client creating
//     *
//     * @param string $url - url сайта
//     * @param string $apiKey - ключ API
//     * @param string $site - символьный код сайта
//     * @return mixed
//     */
//    public function __construct($url, $apiKey, $site = null)
//    {
//        if ('/' != substr($url, strlen($url) - 1, 1)) {
//            $url .= '/';
//        }
//        
//        $url = $url . 'api/' . self::VERSION;
//        
//        if (false === stripos($url, 'https://')) {
//            throw new \InvalidArgumentException('API schema requires HTTPS protocol');
//        }
//
//        $this->url = $url;
//        $this->defaultParameters = array('apiKey' => $apiKey);
//        $this->siteCode = $site;
//    }
//
//    /* Методы для работы с заказами */
//    /**
//     * Получение заказа по id
//     *
//     * @param string $id - идентификатор заказа
//     * @param string $by - поиск заказа по id или externalId
//     * @param string $site - символьный код сайта
//     * @return ApiResponse - информация о заказе
//     */
//    public function ordersGet($id, $by = 'externalId', $site = null)
//    {
//        $this->checkIdParameter($by);
//        
//        return $this->makeRequest("/orders/$id", self::METHOD_GET, $this->fillSite($site, array(
//            'by' => $by
//        )));
//    }
//
//    /**
//     * Получение списка заказов, удовлетворяющих заданному фильтру 
//     *
//     * @param array $filter - фильтры
//     * @param int $page - страница
//     * @param int $limit - ограничение на размер выборки
//     * @return ApiResponse - информация о заказах
//     */
//    public function ordersList(array $filter = array(), $page = null, $limit = null)
//    {
//        $parameters = array();
//        if (sizeof($filter)) {
//            $parameters['filter'] = $filter;
//        }
//        if (null !== $page) {
//            $parameters['page'] = (int) $page;
//        }
//        if (null !== $limit) {
//            $parameters['limit'] = (int) $limit;
//        }
//        return $this->makeRequest('/orders', self::METHOD_GET, $parameters);
//    }
//
//    /**
//     * Создание заказа
//     *
//     * @param array $order - информация о заказе
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function ordersCreate($order, $site = null)
//    {
//        if (!sizeof($order)) {
//            throw new \InvalidArgumentException('Parameter `order` must contains a data');
//        }
//        
//        return $this->makeRequest("/orders/create", self::METHOD_POST, $this->fillSite($site, array(
//            'order' => json_encode($order)
//        )));
//    }
//
//    /**
//     * Изменение заказа
//     *
//     * @param array $order - информация о заказе
//     * @param string $by - изменение заказа по id или externalId
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function orderEdit($order, $by = 'externalId', $site = null)
//    {
//        if (!sizeof($order)) {
//            throw new \InvalidArgumentException('Parameter `order` must contains a data');
//        }
//        
//        $this->checkIdParameter($by);
//        
//        if (!isset($order[$by])) {
//            throw new \InvalidArgumentException(sprintf('Order array must contain the "%s" parameter.', $by));
//        }
//        
//        return $this->makeRequest(
//            "/orders/" . $order[$by] . "/edit",
//            self::METHOD_POST,
//            $this->fillSite($site, array(
//                'order' => json_encode($order),
//                'by' => $by,
//            ))
//        );
//    }
//
//    /**
//     * Пакетная загрузка заказов
//     *
//     * @param array $orders - массив заказов
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function orderUpload($orders, $site = null)
//    {
//        if (!sizeof($orders)) {
//            throw new \InvalidArgumentException('Parameter `orders` must contains array of the orders');
//        }
//        
//        return $this->makeRequest("/orders/upload", self::METHOD_POST, $this->fillSite($site, array(
//            'orders' => json_encode($orders),
//        )));
//    }
//
//    /**
//     * Обновление externalId у заказов с переданными id
//     *
//     * @param array $order - массив, содержащий id и externalId заказа
//     * @return ApiResponse
//     */
//    public function orderFixExternalIds($order)
//    {
//        if (!sizeof($order)) {
//            throw new \InvalidArgumentException('Method parameter must contains at least one IDs pair');
//        }
//        
//        return $this->makeRequest("/orders/fix-external-ids", self::METHOD_POST, array(
//            'orders' => json_encode($order),
//        ));
//    }
//
//    /**
//     * Получение последних измененных заказов
//     *
//     * @param \DateTime|string|int $startDate - начальная дата и время выборки (Y-m-d H:i:s)
//     * @param \DateTime|string|int $endDate   - конечная дата и время выборки (Y-m-d H:i:s)
//     * @param int $limit - ограничение на размер выборки
//     * @param int $offset - сдвиг
//     * @param bool $skipMyChanges
//     * @return ApiResponse
//     */
//    public function orderHistory(
//        $startDate = null, 
//        $endDate = null, 
//        $limit = 100, 
//        $offset = 0,
//        $skipMyChanges = true
//    ) {
//        $parameters = array();
//        
//        if ($startDate) {
//            $parameters['startDate'] = $this->ensureDateTime($startDate);
//        }
//        if ($endDate) {
//            $parameters['endDate'] = $this->ensureDateTime($endDate);
//        }
//        if ($limit) {
//            $parameters['limit'] = (int) $limit;
//        }
//        if ($offset) {
//            $parameters['offset'] = (int) $offset;
//        }
//        if ($skipMyChanges) {
//            $parameters['skipMyChanges'] = (bool) $skipMyChanges;
//        }
//        
//        return $this->makeRequest('/orders/history', self::METHOD_GET, $parameters);
//    }
//
//    /* Методы для работы с клиентами */
//    /**
//     * Получение клиента по id
//     *
//     * @param string $id - идентификатор
//     * @param string $by - поиск заказа по id или externalId
//     * @param string $site - символьный код сайта
//     * @return array - информация о клиенте
//     */
//    public function customerGet($id, $by = 'externalId', $site = null)
//    {
//        $this->checkIdParameter($by);
//        
//        return $this->makeRequest("/customers/$id", self::METHOD_GET, $this->fillSite($site, array(
//            'by' => $by
//        )));
//    }
//
//    /**
//     * Получение списка клиентов в соответсвии с запросом
//     *
//     * @param array $filter - фильтры
//     * @param int $page - страница
//     * @param int $limit - ограничение на размер выборки
//     * @return ApiResponse
//     */
//    public function customersList(array $filter = array(), $page = null, $limit = null)
//    {
//        $parameters = array();
//        
//        if (sizeof($filter)) {
//            $parameters['filter'] = $filter;
//        }
//        
//        if (null !== $page) {
//            $parameters['page'] = (int) $page;
//        }
//        
//        if (null !== $limit) {
//            $parameters['limit'] = (int) $limit;
//        }
//        
//        return $this->makeRequest('/customers', self::METHOD_GET, $parameters);
//    }    
//
//    /**
//     * Создание клиента
//     *
//     * @param array $customer - информация о клиенте
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function customersCreate(array $customer, $site = null)
//    {
//        if (!sizeof($customer)) {
//            throw new \InvalidArgumentException('Parameter `customer` must contains a data');
//        }
//        
//        return $this->makeRequest("/customers/create", self::METHOD_POST, $this->fillSite($site, array(
//            'customer' => json_encode($customer)
//        )));
//    }
//
//    /**
//     * Редактирование клиента
//     *
//     * @param array $customer - информация о клиенте
//     * @param string $by - изменение клиента по id или externalId
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function customerEdit($customer, $by = 'externalId', $site = null)
//    {
//        if (!sizeof($customer)) {
//            throw new \InvalidArgumentException('Parameter `customer` must contains a data');
//        }
//        
//        $this->checkIdParameter($by);
//        
//        if (!isset($customer[$by])) {
//            throw new \InvalidArgumentException(sprintf('Customer array must contain the "%s" parameter.', $by));
//        }
//        
//        return $this->makeRequest(
//            "/customers/" . $customer[$by] . "/edit",
//            self::METHOD_POST,
//            $this->fillSite($site, array(
//                'customer' => json_encode($customer),
//                'by' => $by,
//            )
//        ));
//    }
//
//    /**
//     * Пакетная загрузка клиентов
//     *
//     * @param array $customers - массив клиентов
//     * @param string $site - символьный код сайта
//     * @return ApiResponse
//     */
//    public function customerUpload($customers, $site = null)
//    {
//        if (!sizeof($customers)) {
//            throw new \InvalidArgumentException('Parameter `customers` must contains array of the customers');
//        }
//        
//        return $this->makeRequest("/customers/upload", self::METHOD_POST, $this->fillSite($site, array(
//            'customers' => json_encode($customers),
//        )));
//    }
//
//    /**
//     * Обновление externalId у клиентов с переданными id
//     *
//     * @param array $customers - массив, содержащий id и externalId заказа
//     * @return array
//     */
//    public function customerFixExternalIds($customers)
//    {
//        if (!sizeof($customers)) {
//            throw new \InvalidArgumentException('Method parameter must contains at least one IDs pair');
//        }
//        
//        return $this->makeRequest("/customers/fix-external-ids", self::METHOD_POST, array(
//            'customers' => json_encode($customers),
//        ));
//    }
//
//    /* Методы для работы со справочниками */
//    
//    /**
//     * Получение списка типов доставки
//     *
//     * @return ApiResponse
//     */
//    public function deliveryTypesList()
//    {
//        return $this->makeRequest('/reference/delivery-types', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование типа доставки
//     *
//     * @param array $delivery - информация о типе доставки
//     * @return ApiResponse
//     */
//    public function deliveryTypeEdit($delivery)
//    {
//        if (!isset($delivery['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/delivery-types/' . $delivery['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'deliveryType' => json_encode($delivery)
//            )
//        );
//    }
//
//    /**
//     * Получение списка служб доставки
//     *
//     * @return ApiResponse
//     */
//    public function deliveryServicesList()
//    {
//        return $this->makeRequest('/reference/delivery-services', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование службы доставки
//     *
//     * @param array $delivery - информация о типе доставки
//     * @return ApiResponse
//     */
//    public function deliveryServiceEdit($delivery)
//    {
//        if (!isset($delivery['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/delivery-services/' . $delivery['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'deliveryService' => json_encode($delivery)
//            )
//        );
//    }
//
//
//    /**
//     * Получение списка типов оплаты
//     *
//     * @return ApiResponse
//     */
//    public function paymentTypesList()
//    {
//        return $this->makeRequest('/reference/payment-types', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование типа оплаты
//     *
//     * @param array $paymentType - информация о типе оплаты
//     * @return ApiResponse
//     */
//    public function paymentTypesEdit($paymentType)
//    {
//        if (!isset($paymentType['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/payment-types/' . $paymentType['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'paymentType' => json_encode($paymentType)
//            )
//        );
//    }
//
//
//    /**
//     * Получение списка статусов оплаты
//     *
//     * @return ApiResponse
//     */
//    public function paymentStatusesList()
//    {
//        return $this->makeRequest('/reference/payment-statuses', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование статуса оплаты
//     *
//     * @param array $paymentStatus - информация о статусе оплаты
//     * @return ApiResponse
//     */
//    public function paymentStatusesEdit($paymentStatus)
//    {
//        if (!isset($paymentStatus['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/payment-statuses/' . $paymentStatus['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'paymentStatus' => json_encode($paymentStatus)
//            )
//        );
//    }
//
//
//    /**
//     * Получение списка типов заказа
//     *
//     * @return ApiResponse
//     */
//    public function orderTypesList()
//    {
//        return $this->makeRequest('/reference/order-types', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование типа заказа
//     *
//     * @param array $orderType - информация о типе заказа
//     * @return ApiResponse
//     */
//    public function orderTypesEdit($orderType)
//    {
//        if (!isset($orderType['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/order-types/' . $orderType['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'orderType' => json_encode($orderType)
//            )
//        );
//    }
//
//
//    /**
//     * Получение списка способов оформления заказа
//     *
//     * @return ApiResponse
//     */
//    public function orderMethodsList()
//    {
//        return $this->makeRequest('/reference/order-methods', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование способа оформления заказа
//     *
//     * @param array $orderMethod - информация о способе оформления заказа
//     * @return ApiResponse
//     */
//    public function orderMethodsEdit($orderMethod)
//    {
//        if (!isset($orderMethod['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/order-methods/' . $orderMethod['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'orderMethod' => json_encode($orderMethod)
//            )
//        );
//    }
//
//    /**
//     * Получение списка статусов заказа
//     *
//     * @return ApiResponse
//     */
//    public function orderStatusesList()
//    {
//        return $this->makeRequest('/reference/statuses', self::METHOD_GET);
//    }
//
//    /**
//     * Получение списка сайтов
//     *
//     * @return ApiResponse
//     */
//    public function sitesList()
//    {
//        return $this->makeRequest('/reference/sites', self::METHOD_GET);
//    }
//
//    /**
//     * Редактирование статуса заказа
//     *
//     * @param array $status - информация о статусе заказа
//     * @return ApiResponse
//     */
//    public function orderStatusEdit($status)
//    {
//        if (!isset($status['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/statuses/' . $status['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'status' => json_encode($status)
//            )
//        );
//    }
//
//
//    /**
//     * Получение списка групп статусов заказа
//     *
//     * @return ApiResponse
//     */
//    public function orderStatusGroupsList()
//    {
//        return $this->makeRequest('/reference/status-groups', self::METHOD_GET);
//    }
//
//    /**
//     * Обновление статистики
//     *
//     * @return ApiResponse
//     */
//    public function statisticUpdate()
//    {
//        return $this->makeRequest('/statistic/update', self::METHOD_GET);
//    }
//    
//    /**
//     * Обновление остатков
//     *
//     * @return ApiResponse
//     */
//    public function storeUpload($data, $site)
//    {
//        if (!sizeof($data)) {
//            throw new \InvalidArgumentException('Parameter `site` must contains array of the customers');
//        }
//
//        return $this->makeRequest('/store/inventories/upload', self::METHOD_POST, $this->fillSite($site, array('offers' => json_encode($data))));
//    }
//
//    /**
//     * Редактирование сведений о складе
//     *
//     * @return ApiResponse
//     */
//    public function storesEdit($data)
//    {
//        if (!isset($data['code'])) {
//            throw new \InvalidArgumentException('Data must contain "code" parameter.');
//        }
//        
//        return $this->makeRequest(
//            '/reference/stores/' . $data['code'] . '/edit',
//            self::METHOD_POST,
//            array(
//                'store' => json_encode($data)
//            )
//        );
//    }
//
//    /**
//     * @return \DateTime
//     */
//    public function getGeneratedAt() 
//    {
//        return $this->generatedAt;
//    }
//    
//    protected function ensureDateTime($value)
//    {
//        if ($value instanceof \DateTime) {
//            return $value->format('Y-m-d H:i:s');
//        } elseif (is_int($value)) {
//            return date('Y-m-d H:i:s', $value);
//        }
//
//        return $value;
//    }
//    
//    /**
//     * Check ID parameter
//     *
//     * @param string $by
//     * @return bool
//     */
//    protected function checkIdParameter($by)
//    {
//        $allowedForBy = array('externalId', 'id');
//        if (!in_array($by, $allowedForBy)) {
//            throw new \InvalidArgumentException(sprintf(
//                'Value "%s" for parameter "by" is not valid. Allowed values are %s.',
//                $by,
//                implode(', ', $allowedForBy)
//            ));
//        }
//        return true;
//    }
//    
//    /**
//     * Fill params by site value
//     *
//     * @param string $site
//     * @param array $params
//     * @return array
//    */
//    protected function fillSite($site, array $params)
//    {
//        if ($site) {
//            $params['site'] = $site;
//        } elseif ($this->siteCode) {
//            $params['site'] = $this->siteCode;
//        }
//        
//        return $params;
//    }
//    
//    /**
//     * Make HTTP request
//     *
//     * @param string $path
//     * @param string $method (default: 'GET')
//     * @param array $parameters (default: array())
//     * @param int $timeout
//     * @return ApiResponse
//     */
//    public function makeRequest($path, $method, $parameters = array(), $timeout = 30)
//    {
//        $allowedMethods = array(self::METHOD_GET, self::METHOD_POST);
//        if (!in_array($method, $allowedMethods)) {
//            throw new \InvalidArgumentException(sprintf(
//                'Method "%s" is not valid. Allowed methods are %s',
//                $method,
//                implode(', ', $allowedMethods)
//            ));
//        }
//
//        $parameters = array_merge($this->defaultParameters, $parameters);
//
//        $path = $this->url . $path;
//        if (self::METHOD_GET === $method && sizeof($parameters)) {
//            $path .= '?' . http_build_query($parameters);
//        }
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $path);
//        curl_setopt($ch, CURLOPT_FAILONERROR, false);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
//        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout); // times out after 30s
//        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
//
//        if (self::METHOD_POST === $method) {
//            curl_setopt($ch, CURLOPT_POST, true);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
//        }
//
//        $responseBody = curl_exec($ch);
//        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//
//        $errno = curl_errno($ch);
//        $error = curl_error($ch);
//        curl_close($ch);
//
//        if ($errno) {
//            throw new CurlException($error, $errno);
//        }
//        
//        $result = json_decode($responseBody, true);
//        
//        if (isset($result['generatedAt'])) {
//            $this->generatedAt = new \DateTime($result['generatedAt']);
//            unset($result['generatedAt']);
//        }
//        
//        return new ApiResponse($statusCode, $responseBody);
//    }
//}
