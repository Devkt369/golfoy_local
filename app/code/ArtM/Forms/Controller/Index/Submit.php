<?php
namespace ArtM\Forms\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use ArtM\Forms\Model\FormsFactory;
use ArtM\Common\Model\ImageCompressor;

class Submit extends Action
{
    protected $resultRedirectFactory;
    protected $resource;
    protected $formsFactory;
    protected $messageManager;
    protected $imageCompressor;

    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ResourceConnection $resource,
        FormsFactory $formsFactory,
        ManagerInterface $messageManager,
        ImageCompressor $imageCompressor
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resource = $resource;
        $this->formsFactory = $formsFactory;
        $this->messageManager = $messageManager;
        $this->imageCompressor = $imageCompressor;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $isAjax = $this->getRequest()->isXmlHttpRequest();
        $debugMode = (int)$this->getRequest()->getParam('artm_form_debug', 0) === 1;

        $respondError = function ($message, array $extra = []) use ($isAjax, $resultRedirect) {
            if ($isAjax) {
                $payload = array_merge([
                    'success' => false,
                    'message' => (string)$message,
                ], $extra);
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($payload);
            }

            $this->messageManager->addErrorMessage(__($message));
            return $resultRedirect->setRefererOrBaseUrl();
        };

        $respondSuccess = function ($message) use ($isAjax, $resultRedirect) {
            if ($isAjax) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                    'success' => true,
                    'message' => (string)$message,
                ]);
            }

            $this->messageManager->addSuccessMessage(__($message));
            return $resultRedirect->setRefererOrBaseUrl();
        };

        if (!$data || empty($data['form_id'])) {
            return $respondError('Invalid form submission.');
        }
        $form = $this->formsFactory->create()->load($data['form_id']);
        if (!$form->getId()) {
            return $respondError('Form not found.');
        }
        // --- CAPTCHA VALIDATION ---
        $captchaEnabled = (int)$form->getData('form_captcha_enabled');
        $captchaType = $form->getData('form_captcha_type');
        if ($captchaEnabled && $captchaType === 'simple') {
            @session_start();
            $expected = isset($_SESSION['artm_captcha_answer']) ? (int)$_SESSION['artm_captcha_answer'] : null;
            $userVal = isset($data['artm_captcha_input']) ? (int)trim($data['artm_captcha_input']) : null;
            if ($expected !== null && $userVal !== $expected) {
                return $respondError('Incorrect captcha answer. Please try again.');
            }
            unset($_SESSION['artm_captcha_answer']); // Prevent reuse
        }
        try {
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_form_submissions');
            $fields = isset($data['fields']) ? $data['fields'] : [];

        // --- File/Image Upload Handling ---
        if (!empty($_FILES['fields'])) {
            $mediaBase = BP . '/pub/media/artm_forms_uploads/';
            if (!is_dir($mediaBase)) {
                @mkdir($mediaBase, 0777, true);
            }
            foreach ($_FILES['fields']['name'] as $fieldName => $fileInfo) {
                // Handle multiple files (array) and single file
                $isMultiple = is_array($fileInfo);
                if ($isMultiple) {
                    $fields[$fieldName] = [];
                    foreach ($fileInfo as $idx => $name) {
                        if (!empty($name) && isset($_FILES['fields']['tmp_name'][$fieldName][$idx])) {
                            $tmpName = $_FILES['fields']['tmp_name'][$fieldName][$idx];
                            $error = $_FILES['fields']['error'][$fieldName][$idx];
                            if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                                $ext = pathinfo($name, PATHINFO_EXTENSION);
                                if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp','pdf'])) {
                                   return $respondError('Invalid file type. Only JPG, JPEG, PNG, WEBP, PDF allowed.', ['file' => $ext]);
                                }
                                $safeName = uniqid('file_') . '.' . $ext;
								$dest = $mediaBase . $safeName;
								if (move_uploaded_file($tmpName, $dest)) {
									$this->imageCompressor->maybeCompressImage($dest);
									$fields[$fieldName][] = '/media/artm_forms_uploads/' . $safeName;
								}
                            }
                        }
                    }
                } else {
                    if (!empty($fileInfo) && isset($_FILES['fields']['tmp_name'][$fieldName])) {
                        $tmpName = $_FILES['fields']['tmp_name'][$fieldName];
                        $error = $_FILES['fields']['error'][$fieldName];
                        if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                            $ext = pathinfo($fileInfo, PATHINFO_EXTENSION);
                            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp','pdf'])) {
                                return $respondError('Invalid file type. Only JPG, JPEG, PNG, WEBP, PDF allowed.', ['file' => $ext]);
                            }
                            $safeName = uniqid('file_') . '.' . $ext;
								$dest = $mediaBase . $safeName;
								if (move_uploaded_file($tmpName, $dest)) {
									$this->imageCompressor->maybeCompressImage($dest);
									$fields[$fieldName] = '/media/artm_forms_uploads/' . $safeName;
								}
                        }
                    }
                }
            }
        }
            // --- End File/Image Upload Handling ---

            $connection->insert($table, [
                'form_name' => $form->getData('form_name'),
                'form_identifier' => $form->getData('form_identifier'),
                'submitted_data' => json_encode($fields),
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_read' => 0,
            ]);
        } catch (\Throwable $e) {
            $extra = [];
            if ($debugMode) {
                $extra['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
            return $respondError('There was an error submitting the form. Please try again.', $extra);
        }

        return $respondSuccess('Thank you for your submission!');
    }
}
