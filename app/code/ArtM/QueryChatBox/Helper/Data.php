<?php

namespace ArtM\QueryChatBox\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use ArtM\QueryChatBox\Block\ChatBox\ChatBox;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends AbstractHelper
{
    protected $scopeConfig;
    /**
     * @var Magento\Framework\Mail\TransportBuilder
     * */
    protected $transportBuilder;

    /**
     * Chat BOx
     *
     * @var ArtM\QueryChatBox\Block\ChatBox\ChatBox
     */
    protected $ChatBox;

    /**
     * Query helper
     *
     * @param Context $context
     * @param ChatBox $ChatBox
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ChatBox $ChatBox,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
    ){
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->ChatBox = $ChatBox;
    }

    public function send_mail($name, $email, $phoneNumber, $query, $customer_email, $queryType, $customer_name = null){  

        $adminEmail = $this->ChatBox->getQueryTypeConfig('querychat/query/query_senton_email');
        $expertEmail = $this->ChatBox->getQueryTypeConfig('querychat/query/query_senton_expert');
        if($customer_email == $email){
            $templateVars = [];
            $sendor_name = 'Golfoy.com';
            $sendor_email = 'contact@golfoy.com';
            $bcc = 'tech1@golfoy.com';
            $subject = "Your Query has been submitted successfully";
            $templateidentifier = '16';
                    $templateVars = [
                        'cname' => $name,
                        'cemail' => $email,
                        'ctelephone' => $phoneNumber,
                        'querytype' => $queryType,
                        'cquery' => $query,
                        'subj'  => $subject
                    ];          
            $this->transportBuilder
            ->setTemplateIdentifier($templateidentifier)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
            ->setTemplateVars($templateVars)
            ->setFrom(['name' => $sendor_name,'email' => $sendor_email])
            ->addTo($email)
            ->addCc($expertEmail)
            ->addBcc($adminEmail);
            $transport = $this->transportBuilder->getTransport();
            // die('asdfsd');
            if (!isset($transport)) {
                $transport = $this->transportBuilder->getTransport();
            }
            $transport->sendMessage();
        }else{
            $templateVars = [];
            $sendor_name = 'Golfoy.com';
            $sendor_email = 'contact@golfoy.com';
            $bcc = 'tech1@golfoy.com';
            $subject = $customer_name." query has been submitted successfully for You.";
            $templateidentifier = '16';
                    $templateVars = [
                        'cname' => $name,
                        'cemail' => $email,
                        'ctelephone' => $phoneNumber,
                        'querytype' => $queryType,
                        'cquery' => $query,
                        'subj'  => $subject
                    ];          
            $this->transportBuilder
            ->setTemplateIdentifier($templateidentifier)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
            ->setTemplateVars($templateVars)
            ->setFrom(['name' => $sendor_name,'email' => $sendor_email])
            // ->addTo(['tech1@golfoy.com'])
            ->addTo($email)
            ->addCc([$adminEmail])
            ->addBcc($adminEmail);
            $transport = $this->transportBuilder->getTransport();
            if (!isset($transport)) {
                $transport = $this->transportBuilder->getTransport();
            }
            $transport->sendMessage();
        }
    }  

    public function getQueryTypeConfig($configPath)
    {
        return $this->scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function sendExpertMail($data, $expertEmail){

        if($data['blk_unblk_user'] != true && $expertEmail != null){
            $templateVars = [];
            $sendor_name = 'Golfoy.com';
            $sendor_email = 'contact@golfoy.com';
            $bcc = 'tech1@golfoy.com';
            $subject = "Query has been assigned to you";
            $templateidentifier = '17';
            $templateVars = [
                'cname' => $data['name'],
                'cemail' => $data['email'],
                'ctelephone' => $data['phone_number'],
                'querytype' => $data['query_type'],
                'cquery' => $data['query'],
            ];          
            $this->transportBuilder
            ->setTemplateIdentifier($templateidentifier)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
            ->setTemplateVars($templateVars)
            ->setFrom(['name' => $sendor_name,'email' => $sendor_email])
            ->addTo($expertEmail);
            // ->addCc($expertEmail)
            // ->addBcc($adminEmail);
            $transport = $this->transportBuilder->getTransport();
            if (!isset($transport)) {
                $transport = $this->transportBuilder->getTransport();
            }
            $transport->sendMessage();
        }
    }

}
