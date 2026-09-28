<?php
namespace ArtM\ProductFeed\Controller\Feed;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use ArtM\ProductFeed\Model\FeedGenerator;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    protected $_feedGenerator;

    public function __construct(
        Context $context,
        FeedGenerator $feedGenerator
    ) {
        parent::__construct($context);
        $this->_feedGenerator = $feedGenerator;
    }

    public function execute()
    {
        $fileName = 'google_reviews_feed.xml'; 

        $fileContent = $this->_feedGenerator->getFeedFile($fileName);

        if ($fileContent) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('Content-Type', 'application/xml', true)
                ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"', true)
                ->setContents($fileContent);

            return $result;
        }
        $filePath = $this->_feedGenerator->generateFeed();
        return $this->_serveFile($filePath);
    }

    protected function _serveFile($filePath)
    {
        // Serve the newly generated file
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', 'application/xml')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->setContents(file_get_contents($filePath));
        return $result;
    }
}
