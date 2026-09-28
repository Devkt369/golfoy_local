<?php
namespace ArtM\Analysis\Controller\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;

class Monthly extends \Magento\Framework\App\Action\Action
{
    protected $transportBuilder;
    protected $filesystem;
    protected $helper;
    protected $request;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        TransportBuilder $transportBuilder,
        \Magento\Framework\Filesystem $filesystem,
        \ArtM\Analysis\Helper\Data $helper,
        HttpRequest $request
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->filesystem = $filesystem;
        $this->helper = $helper;
        $this->request = $request;
    }

    public function execute()
    {
        $userAgent = $this->request->getHeader('User-Agent');
        if (strpos($userAgent, 'RunByCurlAgent') === false) {
            echo 'Function not allowed.';
            return;
        }
        date_default_timezone_set('Asia/Kolkata');   
        $currentDate = new \DateTime();
        $previousMonth = $currentDate->modify('-1 month')->format('M, Y');

        // Generate file paths (local)
        $filePaths = [
            $this->helper->Monthlysales(),
            $this->helper->MonthlysalesOrder(),
            $this->helper->MonthlysalesOrderReturned(),
            $this->helper->MonthlysalesOrderReplaced(),
            $this->helper->MonthlysalesReward(),
            $this->helper->MonthlysalesCoupon(),
            $this->helper->MonthlysalesCP(),
            $this->helper->MonthlysalesAttr(),
            $this->helper->MonthlyCustomers(),
            $this->helper->MonthlysalesClearance(),
        ];

        // Base media URL
        $mediaUrl = $this->_objectManager
            ->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $linksHtml = '';
        foreach ($filePaths as $fileName) {
            $downloadUrl = $mediaUrl . 'wmsales/' . $fileName;
            $linksHtml .= <<<HTML
                <p>
                    <a href="{$downloadUrl}" target="_blank" 
                       style="display:inline-block; border: 1px solid; padding:8px 14px; text-decoration:none; border-radius:4px;">
                       Download {$fileName}
                    </a>
                </p>
            HTML;
        }

        // HTML Email Body
        $htmlBody = <<<HTML
            <p>Hello Team,</p>
            <p>The monthly sales report for <b>{$previousMonth}</b> is ready.</p>
            <p>Please use the following links to download the reports:</p>
            {$linksHtml}
            <p>Regards,<br><b>Golfoy Admin</b></p>
        HTML;

        // Send email via TransportBuilder (Template ID 21)
        try {
            $this->transportBuilder
                ->setTemplateIdentifier(21)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => 1
                ])
                ->setTemplateVars([
                    'subject' => "Golfoy: Monthly {$previousMonth} Sales Report",
                    'content' => $htmlBody
                ])
                ->setFrom([
                    'name' => 'Golfoy Admin',
                    'email' => 'admin@golfoy.com'
                ])
                ->addTo('tushar@golfoy.com', 'Tushar')
                ->addCc('rohit@golfoy.com', 'Rohit')
                ->addCc('manav.sales@golfoy.com', 'Manav')
                ->addCc('ventures.kts@gmail.com', 'KTS')
                ->addCc('sanchit@golfoy.com', 'Sanchit Garg');

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            echo "✅ Monthly email sent successfully.";
        } catch (\Exception $e) {
            echo "❌ Error sending email: " . $e->getMessage();
        }
    }
}
