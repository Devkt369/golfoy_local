<?php
namespace ArtM\BulkOrder\Controller\Upload;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use ArtM\BulkOrder\Model\PartnersBulkorderFactory; 
use ArtM\BulkOrder\Helper\Data as DataHelper;

class Index extends Action
{
    protected $uploaderFactory;
    protected $filesystem;
    protected $resultJsonFactory;
    protected $partnersBulkorderFactory;
    protected $dataHelper;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        JsonFactory $resultJsonFactory,
        PartnersBulkorderFactory $partnersBulkorderFactory,
        DataHelper $dataHelper
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->partnersBulkorderFactory = $partnersBulkorderFactory;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        try {

            if (!isset($_FILES['bulk_file']) || !$_FILES['bulk_file']['name']) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'No file uploaded.'
                ]);
            }

            // Validate extension manually before upload
            $fileName = $_FILES['bulk_file']['name'];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'csv') {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'Only CSV files are allowed.'
                ]);
            }

            // Validate CSV headers directly from tmp_name
            $tmpPath = $_FILES['bulk_file']['tmp_name'];
            $handle = fopen($tmpPath, 'r');
            if ($handle === false) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'Cannot open uploaded CSV file.'
                ]);
            }

            $headers = fgetcsv($handle);
            fclose($handle);

            if (!$headers) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'CSV is empty or invalid.'
                ]);
            }

            $headers = array_map('strtolower', array_map('trim', $headers));
            $expected = ['sku', 'qty'];

            if ($headers !== $expected) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'CSV must contain only two columns: sku, qty (in order).'
                ]);
            }

            $skus = [];
            $handle = fopen($tmpPath, 'r');
            while (($row = fgetcsv($handle)) !== false) {
                $skus[] = trim($row[0]);
            }
            // update skus array and remove header

            fclose($handle);

            if (!isset($skus[1])) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'No SKUs found.'
                ]);
            }
            array_shift($skus);
            $unavailableSkus = [];
            foreach ($skus as $sku) {
                if (!$this->dataHelper->isSkuAvailable($sku)) {
                    $unavailableSkus[] = $sku;
                }
            }
            // print_r($unavailableSkus);die('fghfgh');
            if ($unavailableSkus) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'The following SKUs are not available: <strong style="color: black;">' . implode(', ', $unavailableSkus) . '</strong>'
                ]);
            }

            $uploader = $this->uploaderFactory->create(['fileId' => 'bulk_file']);
            $uploader->setAllowedExtensions(['csv']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $targetFolder = 'bulkorder';
            $varDirectory->create($targetFolder);
            $result = $uploader->save($varDirectory->getAbsolutePath($targetFolder));

            if (!$result) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => 'File cannot be saved.'
                ]);
            }
            
            $bulkOrderModel = $this->partnersBulkorderFactory->create();
            $bulkOrderModel->setDealerEmail($this->_getCustomerEmail()) 
                ->setDealerName($this->_getCustomerName())
                ->setOrderFile($targetFolder . '/' . $result['file']); 

            $bulkOrderModel->save();

            return $resultJson->setData([
                'success' => true,
                'file' => $result['file'],
                'name' => $result['name'],
                'path' => $targetFolder . '/' . $result['file']
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function _getCustomerEmail()
    {
        return $this->_getCustomer()->getEmail();
    }

    private function _getCustomerName()
    {
        return $this->_getCustomer()->getName();
    }

    private function _getCustomer()
    {
        return $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
    }
}
