<?php
namespace ArtM\Common\Pdf;

class CustomCreditmemoPdf extends \Magento\Sales\Model\Order\Pdf\Creditmemo
{
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        // ...custom insertOrder logic from your CustomAbstractPdf...
        parent::insertOrder($page, $obj, $putOrderId); // You can replace this with your full custom logic
    }

    protected function insertTotals($page, $source)
    {
        // ...custom insertTotals logic from your CustomAbstractPdf...
        parent::insertTotals($page, $source); // You can replace this with your full custom logic
    }
}
