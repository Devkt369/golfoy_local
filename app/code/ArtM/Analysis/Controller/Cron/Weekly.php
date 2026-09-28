<?php
namespace ArtM\Analysis\Controller\Cron;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Filesystem;
use ArtM\Analysis\Helper\Data;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;

class Weekly extends Action
{
    protected $transportBuilder;
    protected $filesystem;
    protected $helper;
    protected $request;
    protected $storeManager;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        Filesystem $filesystem,
        Data $helper,
        HttpRequest $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->filesystem = $filesystem;
        $this->helper = $helper;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        // $userAgent = $this->request->getHeader('User-Agent');
        // if (strpos($userAgent, 'RunByCurlAgent') === false) {
        //     echo 'Function not allowed.';
        //     return;
        // }
        try {
            $filename = $this->helper->Weeklysales();
            $fileRelativePath = 'wmsales/' . $filename;
            // $filePath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
            //     ->getAbsolutePath($fileRelativePath);
            // var_dump($filePath);die;
            // if (!file_exists($filePath)) {
            //     throw new LocalizedException(__('File not found: %1', $filePath));
            // }
            // $mediaPath = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)
            //     ->getAbsolutePath('wmsales/');

            // if (!is_dir($mediaPath)) {
            //     mkdir($mediaPath, 0755, true);
            // }

            // $publicFilePath = $mediaPath . $filename;
            // copy($filePath, $publicFilePath);

            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $downloadUrl = 'https://staging-golfoy.hyperx.cloud/pub/media/' . 'wmsales/' . rawurlencode($filename);

            date_default_timezone_set('Asia/Kolkata');
            $currentDate = new \DateTime();
            $currentDate->modify('last week Monday');
            $oneWeekAgoFormatted = $currentDate->format('jM');
            $currentDate->add(new \DateInterval('P6D'));
            $yesterdayFormatted = $currentDate->format('jM');

            $subject = 'Golfoy: Weekly Summary ' . $oneWeekAgoFormatted . ' to ' . $yesterdayFormatted . ' sales report';

            $htmlBody = <<<HTML
                <p>Hello Team,</p>
                <p>The weekly sales report is ready.</p>
                <p><button style="background:#e60023; border:none !important;"><a href="{$downloadUrl}" style="color: rgb(255, 255, 255) !important;" target="_blank">
                    Download Now
                </a></button></p>
                <p>Regards,<br>Golfoy Admin</p>
            HTML;

            $this->transportBuilder
                ->setTemplateIdentifier('20') // any simple template id
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
                ->setTemplateVars(['content' => $htmlBody,"subject" => $subject])
                ->setFrom(['email' => 'admin@golfoy.com', 'name' => 'Golfoy Admin'])
                ->addTo('tushar@golfoy.com', 'Tushar')
                ->addCc('golfoy.e01@gmail.com', 'Golfoy e01')
                ->addCc('rohit@golfoy.com', 'Rohit')
                ->addCc('manav.sales@golfoy.com', 'Manav')
                ->addCc('ventures.kts@gmail.com', 'KTS')
                ->addCc('sanchit@golfoy.com', 'Sanchit Garg')
                ->setReplyTo('admin@golfoy.com');
                // ->setSubject($subject);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

            echo "Mail sent successfully with download link: " . $downloadUrl;

        } catch (\Exception $e) {
            echo "Error sending email: " . $e->getMessage();
        }
    }
}
