<?php

namespace ArtM\QueryChatBox\Controller\ChatBox;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
use Psr\Log\LoggerInterface;
use ArtM\QueryChatBox\Helper\Data;
use Meetanshi\Sms\Helper\Data as smsHelperData;

class Submit extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    protected $escaper;

    /**
     * @var QueryChatBoxInterfaceFactory
     */
    protected $queryChatBoxFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    protected $QueryChatBoxHelper;
    protected $smsHelperData;

    /**
     * Submit controller
     *
     * @param Context $context
     * @param Escaper $escaper
     * @param JsonFactory $resultJsonFactory
     * @param QueryChatBoxFactory $queryChatBoxFactory
     * @param Data $QueryChatBoxHelper
     * @param smsHelperData $smsHelperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Escaper $escaper,
        JsonFactory $resultJsonFactory,
        QueryChatBoxFactory $queryChatBoxFactory,
        Data $QueryChatBoxHelper,
        smsHelperData $smsHelperData,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->escaper = $escaper;
        $this->queryChatBoxFactory = $queryChatBoxFactory;
        $this->smsHelperData = $smsHelperData;
        $this->logger = $logger;
        $this->queryHelperData = $QueryChatBoxHelper;
        parent::__construct($context);
    }

    /**
     * Execute method to handle the form submission
     */
    public function execute()
    {
        // Get form data from the request
        $postData = $this->getRequest()->getPostValue();

            if (isset($postData['email']) && isset($postData['query_type']) && isset($postData['query'])) {
                
                $name = $this->escaper->escapeHtml($postData['name']);
                $customer_name = $this->escaper->escapeHtml($postData['customer_name']);
                $email = $this->escaper->escapeHtml($postData['email']);
                $cid = $this->escaper->escapeHtml($postData['cid']);
                $phoneNumber = $this->escaper->escapeHtml($postData['phone_number']);
                $query = $this->escaper->escapeHtml($postData['query']);
                $customer_email = $this->escaper->escapeHtml($postData['customer_email']);
                $queryType = $this->escaper->escapeHtml($postData['query_type']);
                $timestamp = time();
                $cid = substr($timestamp.$cid, -10);
                // die($cid);
                $queryChatBox = $this->queryChatBoxFactory->create();
                $queryChatBox->addData([
                    'query_id' => $cid,
                    'name' => $name,
                    'email' => $email,
                    'customer_email' => $customer_email,
                    'phone_number' => $phoneNumber,
                    'query_type' => $queryType,
                    'query' => $query,
                    'status' => '1', // Default status
                    'query_assign' => '', // Initially not assigned
                    'response_result' => '' // No response yet
                ]);
    
                // Save model data
                $queryChatBox->save();
                $this->queryHelperData->send_mail($name, $email, $phoneNumber, $query, $customer_email, $queryType, $customer_name);

                $smsData = [];
                $smsData['name'] = $name;
                $smsData['phonenumber'] = $phoneNumber;
                $smsData['query'] = $query;
                $smsData['username'] = $customer_name;
                $smsData['customer_email'] = $customer_email;
                $smsData['querytype'] = $queryType;
                $smsData['email'] = $email;
                $this->smsHelperData->sendSmsQuery($smsData, $this->queryHelperData->getQueryTypeConfig('querychat/query/query_senton_sms')); // send sms to admin 
                $this->smsHelperData->sendSmsUserQuery($smsData, $phoneNumber); // send sms to customer

                $response = [
                    'success' => true,
                    'message' => 'Your query has been submitted successfully. An expert will contact you shortly.'
                ];

            } else {
                $response = [
                    'success' => false,
                    'message' => 'There was an error submitting your query. Please try again.'
                ];
            }

        // Return JSON response
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
