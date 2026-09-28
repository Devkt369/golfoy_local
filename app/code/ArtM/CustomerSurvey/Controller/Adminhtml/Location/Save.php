<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use ArtM\CustomerSurvey\Model\ImageUploader;
use ArtM\CustomerSurvey\Model\Location;
use ArtM\CustomerSurvey\Model\LocationFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::location_save';

    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(
        Action\Context $context,
        LocationFactory $locationFactory,
        LocationRepositoryInterface $locationRepository,
        DataPersistorInterface $dataPersistor,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->locationFactory = $locationFactory;
        $this->locationRepository = $locationRepository;
        $this->dataPersistor = $dataPersistor;
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/index');
        }

        $locationId = isset($data['location_id']) ? (int)$data['location_id'] : 0;

        try {
            $location = $locationId
                ? $this->locationRepository->getById($locationId)
                : $this->locationFactory->create();

            $this->validateData($data);

            $location->setName((string)$data['name']);
            $location->setDescription((string)($data['description'] ?? ''));
            $location->setPageType((string)$data['page_type']);
            $location->setUrlPattern((string)($data['url_pattern'] ?? ''));
            $location->setCssSelector((string)$data['css_selector']);
            $location->setPosition((string)$data['position']);
            $location->setStatus((int)($data['status'] ?? 1));

            $screenshot = $this->processScreenshot($data, $location);
            $location->setScreenshot($screenshot);

            $this->locationRepository->save($location);

            $this->messageManager->addSuccessMessage(__('Location has been saved.'));
            $this->dataPersistor->clear('artm_customersurvey_location');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['location_id' => $location->getLocationId()]);
            }

            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to save location right now.'));
        }

        $this->dataPersistor->set('artm_customersurvey_location', $data);
        return $resultRedirect->setPath('*/*/edit', ['location_id' => $locationId ?: null]);
    }

    /**
     * @param array<string, mixed> $data
     * @throws LocalizedException
     */
    private function validateData(array $data): void
    {
        $name = trim((string)($data['name'] ?? ''));
        $pageType = (string)($data['page_type'] ?? '');
        $cssSelector = trim((string)($data['css_selector'] ?? ''));
        $position = (string)($data['position'] ?? '');
        $urlPattern = trim((string)($data['url_pattern'] ?? ''));

        if ($name === '') {
            throw new LocalizedException(__('Location name is required.'));
        }

        if (!in_array($pageType, Location::getAvailablePageTypes(), true)) {
            throw new LocalizedException(__('Please select a valid page type.'));
        }

        if ($cssSelector === '') {
            throw new LocalizedException(__('CSS selector is required.'));
        }

        if (!preg_match('/^[#\.\[\]:>+~\*\w\-\s="\'\(\),]+$/', $cssSelector)) {
            throw new LocalizedException(__('Invalid CSS selector format. Use standard CSS selector syntax.'));
        }

        if (!in_array($position, Location::getAvailablePositions(), true)) {
            throw new LocalizedException(__('Please select a valid position option.'));
        }

        if ($pageType === Location::PAGE_TYPE_CUSTOM_URL && $urlPattern === '') {
            throw new LocalizedException(__('URL Pattern is required for custom URL page type.'));
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param \ArtM\CustomerSurvey\Model\Location $location
     * @return string|null
     */
    private function processScreenshot(array $data, Location $location): ?string
    {
        if (!isset($data['screenshot'])) {
            return $location->getScreenshot() ?: null;
        }

        $screenshot = $data['screenshot'];
        if (is_array($screenshot) && isset($screenshot[0]['name'])) {
            $imageName = (string)$screenshot[0]['name'];
            if (!empty($screenshot[0]['tmp_name'])) {
                $this->imageUploader->moveFileFromTmp($imageName);
            }

            return $imageName;
        }

        if (is_array($screenshot) && !empty($screenshot['delete'])) {
            return null;
        }

        return $location->getScreenshot() ?: null;
    }
}
