<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class Upload extends Action
{
    protected $resultJsonFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $headers = [];
        $rows = [];

        if (!empty($_FILES['csv_file']['tmp_name'])) {

            try {
                // 1. Prepare file info
                $fileName = time() . '_' . $_FILES['csv_file']['name'];
                $destinationPath = BP . '/var/import/Brand_sheets/brand_stock/'; // recommended folder

                // 2. Create folder if not exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $savedFilePath = $destinationPath . $fileName;

                // 3. Move file from temp to server folder
                move_uploaded_file(
                    $_FILES['csv_file']['tmp_name'],
                    $savedFilePath
                );

                // 4. Now read from saved file
                if (($handle = fopen($savedFilePath, "r")) !== false) {

                    $headers = fgetcsv($handle, 1000, ",");

                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                        if (!empty(array_filter($data))) {
                            $rows[] = $data;
                        }
                    }

                    fclose($handle);
                }

            } catch (\Exception $e) {
                return $this->resultJsonFactory->create()->setData([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }

        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'file_path' => $savedFilePath ?? '',
            'headers' => $headers,
            'rows' => $rows
        ]);
    }

    protected function _isAllowed()
    {
        return true;
    }
}