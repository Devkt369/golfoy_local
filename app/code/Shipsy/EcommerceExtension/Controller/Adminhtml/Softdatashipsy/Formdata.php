<?php

namespace Shipsy\EcommerceExtension\Controller\Adminhtml\Softdatashipsy;

class Formdata extends \Magento\Framework\App\Action\Action
{
    protected $_messageManager;
    protected $urlInterface;
    protected $_cookieManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Shipsy\EcommerceExtension\Helper\Data $dataHelper
    ) {
        parent::__construct($context);
        $this->_messageManager = $messageManager;
        $this->urlInterface = $urlInterface;
        $this->_cookieManager = $cookieManager;
        $this->scopeConfig = $scopeConfig;
        $this->dataHelper = $dataHelper;
    }

    public function getAddresses()
    {
        $organisation_id = $this->scopeConfig->getValue('configuration/services/organisation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $base_url = $this->dataHelper->getBaseUrl($this->scopeConfig, $organisation_id);
        
        $headers = [
            'Content-Type:application/json',
            'organisation-id:'.$organisation_id,
            'shop-origin:magento',
            'shop-url:'.$this->urlInterface->getBaseUrl(),
            'customer-id:'.$this->_cookieManager->getCookie('customer-id'),
            'access-token:'.$this->_cookieManager->getCookie('access-token-shipsy')
        ];

        $ch = curl_init($base_url . '/api/ecommerce/getshopdata');

        curl_setopt($ch, CURLOPT_POST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $resultdata = json_decode($result, true);
        return $resultdata;
    }
    
    public function execute()
    {
        $requiredFields = ['customer-reference-number', 'service-type', 'courier-type', 'num-pieces',
        'origin-name', 'origin-number', 'origin-line-1', 'origin-pincode', 'origin-state', 'origin-country',
        'destination-name', 'destination-number', 'destination-line-1', 'destination-pincode', 'destination-state', 'destination-country',
        'cod-collection-mode', 'cod-amount', 'description', 'declared-value', 'weight', 'height', 'length', 'width'
        ];

        try {
            $postRequestParams = $this->getRequest()->getPostValue();
            foreach ($requiredFields as $requiredField) {
                if (!isset($postRequestParams[$requiredField])) {
                    throw new \Exception('Missing required field - ' . $requiredField);
                }
            }

            $addressArray = $this->getAddresses();
            if (array_key_exists('data', $addressArray) && !empty($addressArray['data'])) {
                $allAddresses = $addressArray['data'];
                $forwardAddress = $allAddresses['forwardAddress'];
                $reverseAddress = $allAddresses['reverseAddress'];
                $exceptionalReturnAddress = $allAddresses['exceptionalReturnAddress'];
            } else {
                throw new \Exception('Failed to load addresses');
            }

            $cod_collection_mode = 'cash';
            if (strtolower($postRequestParams['cod-amount']) === '0') {
                $cod_collection_mode = '';
            }

            $customerReferenceNumber = $postRequestParams['customer-reference-number'];
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $salesOrder = $resource->getTableName('sales_order');
            
            $newsql = "SELECT *
            FROM " . $salesOrder . "
            WHERE increment_id = $customerReferenceNumber";
            
            $queryResult = $connection->fetchAll($newsql);
            $resultRedirect = $this->resultRedirectFactory->create();
            $errorMessage = '';
            $samePiece = false;
            if (array_key_exists('multiPieceCheck', $postRequestParams)) {
                $samePiece = true;
            }

            $dataToSendArray = [
                'consignments' =>  [
                    [
                        'customer_code' => $this->_cookieManager->getCookie('customer-code'),
                        'consignment_type' => $postRequestParams['consignment-type'],
                        'service_type_id' => $postRequestParams['service-type'],
                        'reference_number' => '',
                        'load_type' => $postRequestParams['courier-type'],
                        'customer_reference_number' => $customerReferenceNumber,
                        'commodity_name' => 'Other',
                        'num_pieces' => $postRequestParams['num-pieces'],
                        'origin_details' =>  [
                            'name' => $postRequestParams['origin-name'],
                            'phone' => $postRequestParams['origin-number'],
                            'alternate_phone' => (!empty($postRequestParams['origin-alt-number'])) ? $postRequestParams['origin-alt-number'] : $postRequestParams['origin-number'],
                            'address_line_1' => $postRequestParams['origin-line-1'],
                            'address_line_2' => $postRequestParams['origin-line-2'],
                            'pincode' => $postRequestParams['origin-pincode'],
                            'city' => $postRequestParams['origin-city'],
                            'state' => $postRequestParams['origin-state'],
                            'country' => $postRequestParams['origin-country'],
                        ],
                        'destination_details' =>  [
                            'name' => $postRequestParams['destination-name'],
                            'phone' => $postRequestParams['destination-number'],
                            'alternate_phone' => (!empty($postRequestParams['destination-alt-number'])) ? $postRequestParams['destination-alt-number'] : $postRequestParams['destination-number'],
                            'address_line_1' => $postRequestParams['destination-line-1'],
                            'address_line_2' => $postRequestParams['destination-line-2'],
                            'pincode' => $postRequestParams['destination-pincode'],
                            'city' => $postRequestParams['destination-city'],
                            'state' => $postRequestParams['destination-state'],
                            'country' => $postRequestParams['destination-country'],
                        ],
                        'same_pieces' => $samePiece,
                        'cod_favor_of' => '',
                        'pieces_detail' => [],
                        'cod_collection_mode' => $cod_collection_mode,
                        'cod_amount' => $postRequestParams['cod-amount'],
                        'exceptional_return_details' => $exceptionalReturnAddress,
                    ],
                ]
            ];
            if ($postRequestParams['num-pieces'] === 1 || $samePiece === true) {
                $temp_pieces_details =  [
                    'description' => $postRequestParams['description'],
                    'declared_value' => $postRequestParams['declared-value'],
                    'quantity' => $postRequestParams['quantity'],
                    'weight' => $postRequestParams['weight'],
                    'height' => $postRequestParams['height'],
                    'length' => $postRequestParams['length'],
                    'width' => $postRequestParams['width']
                ];
                array_push($dataToSendArray['consignments'][0]['pieces_detail'], $temp_pieces_details);
            } else {
                for ($index = 0; $index < $postRequestParams['num-pieces']; $index++) {
                    $temp_pieces_details =  [
                        'description' => $postRequestParams['description'][$index],
                        'declared_value' => $postRequestParams['declared-value'][$index],
                        'quantity' => $postRequestParams['quantity'][$index],
                        'weight' => $postRequestParams['weight'][$index],
                        'height' => $postRequestParams['height'][$index],
                        'length' => $postRequestParams['length'][$index],
                        'width' => $postRequestParams['width'][$index]
                    ];
                    array_push($dataToSendArray['consignments'][0]['pieces_detail'], $temp_pieces_details);
                };
            }
            $dataToSendJson = json_encode($dataToSendArray);

            $organisation_id = $this->scopeConfig->getValue('configuration/services/organisation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $base_url = $this->dataHelper->getBaseUrl($this->scopeConfig, $organisation_id);
        
            $headers = [
                'Content-Type:application/json',
                'organisation-id:'.$organisation_id,
                'shop-origin:magento',
                'shop-url:'.$this->urlInterface->getBaseUrl(),
                'customer-id:'.$this->_cookieManager->getCookie('customer-id'),
                'access-token:'.$this->_cookieManager->getCookie('access-token-shipsy')
            ];
            // var_dump($dataToSendJson);
            $ch = curl_init($base_url . '/api/ecommerce/softdata');

            curl_setopt($ch, CURLOPT_POST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToSendJson);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            curl_close($ch);
            $resultdata = json_decode($result, true);
            if (array_key_exists('data', $resultdata) && array_key_exists('reference_number', $resultdata['data'][0]) && strlen($resultdata['data'][0]['reference_number']) > 0) {
                $referenceNumber = $resultdata["data"][0]["reference_number"];
                $storedReferenceNumbers = $queryResult[0]['shipsy_reference_numbers'];
                if (!empty($storedReferenceNumbers)) {
                    throw new \Exception('Order already synced. Reference Number - ' . $storedReferenceNumbers);
                }
                $sqlq = "Update " . $salesOrder . " Set `shipsy_reference_numbers` = '$referenceNumber' Where `increment_id` = '$customerReferenceNumber'";
                $uquery = $connection->query($sqlq);
                $this->_messageManager->addSuccessMessage('Sync successful. Shipsy Reference Number for #'.$postRequestParams["customer-reference-number"]. ' - ' . $referenceNumber);
                return $resultRedirect->setPath('sales/order/index');
            } else {
                if (array_key_exists('data', $resultdata) && array_key_exists('message', $resultdata['data'][0])) {
                    $errorMessage = $resultdata['data'][0]['message'];
                } elseif (array_key_exists('error', $resultdata)) {
                    $errorMessage = $resultdata['error']['message'];
                }
                throw new \Exception($errorMessage);
            }
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage('Failed to sync order - '. $e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
    }
}
