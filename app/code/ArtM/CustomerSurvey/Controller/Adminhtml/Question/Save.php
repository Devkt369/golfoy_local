<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Question;

use ArtM\CustomerSurvey\Api\QuestionOptionRepositoryInterface;
use ArtM\CustomerSurvey\Api\QuestionRepositoryInterface;
use ArtM\CustomerSurvey\Model\Question;
use ArtM\CustomerSurvey\Model\QuestionFactory;
use ArtM\CustomerSurvey\Model\QuestionOptionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption\CollectionFactory as OptionCollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::question_save';

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    /**
     * @var QuestionRepositoryInterface
     */
    private $questionRepository;

    /**
     * @var QuestionOptionRepositoryInterface
     */
    private $optionRepository;

    /**
     * @var QuestionOptionFactory
     */
    private $optionFactory;

    /**
     * @var OptionCollectionFactory
     */
    private $optionCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        QuestionFactory $questionFactory,
        QuestionRepositoryInterface $questionRepository,
        QuestionOptionRepositoryInterface $optionRepository,
        QuestionOptionFactory $optionFactory,
        OptionCollectionFactory $optionCollectionFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->questionFactory = $questionFactory;
        $this->questionRepository = $questionRepository;
        $this->optionRepository = $optionRepository;
        $this->optionFactory = $optionFactory;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        $data = (array)$this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/index');
        }

        $questionId = isset($data['question_id']) ? (int)$data['question_id'] : 0;
        
        // Write debug to file
        $debugFile = BP . '/var/log/customersurvey_upload_debug.log';
        $debugMsg = "\n========== QUESTION SAVE START ==========\n";
        $debugMsg .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $debugMsg .= "Question ID: " . $questionId . "\n";
        $debugMsg .= "POST Data Keys: " . implode(', ', array_keys($data)) . "\n";
        $debugMsg .= "\$_FILES Content:\n" . print_r($_FILES, true);
        $debugMsg .= "\ngetRequest()->getFiles('option_images'):\n" . print_r($this->getRequest()->getFiles('option_images'), true);
        file_put_contents($debugFile, $debugMsg, FILE_APPEND);
        
        $this->logger->debug('Question Save Start', ['question_id' => $questionId]);

        try {
            $question = $questionId
                ? $this->questionRepository->getById($questionId)
                : $this->questionFactory->create();

            $this->validateQuestionData($data);
            $questionType = (string)$data['type'];

            $question->setTitle((string)$data['title']);
            $question->setDescription(isset($data['description']) ? (string)$data['description'] : null);
            $question->setType($questionType);
            $question->setIsRequired(isset($data['is_required']) ? (int)$data['is_required'] : 0);
            $question->setSortOrder(isset($data['sort_order']) ? (int)$data['sort_order'] : 0);
            $question->setStatus(isset($data['status']) ? (int)$data['status'] : 1);

            if ($questionType === Question::TYPE_CHECKBOX) {
                $question->setMinSelection((int)$data['min_selection']);
                $question->setMaxSelection((int)$data['max_selection']);
            } else {
                $question->setMinSelection(null);
                $question->setMaxSelection(null);
            }

            $this->questionRepository->save($question);
            $this->syncQuestionOptions((int)$question->getQuestionId(), $questionType, $data);

            $this->messageManager->addSuccessMessage(__('Question has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['question_id' => $question->getQuestionId()]);
            }

            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to save question right now.'));
        }

        return $resultRedirect->setPath(
            '*/*/edit',
            ['question_id' => $questionId ?: null, '_current' => true]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @throws LocalizedException
     */
    private function validateQuestionData(array $data): void
    {
        $title = trim((string)($data['title'] ?? ''));
        $type = (string)($data['type'] ?? '');

        if ($title === '') {
            throw new LocalizedException(__('Question title is required.'));
        }

        if (!in_array($type, Question::getAvailableTypes(), true)) {
            throw new LocalizedException(__('Please select a valid question type.'));
        }

        if ($type === Question::TYPE_CHECKBOX) {
            $min = (int)($data['min_selection'] ?? 0);
            $max = (int)($data['max_selection'] ?? 0);
            if ($min < 0 || $max < 0) {
                throw new LocalizedException(__('Min/Max selection cannot be negative.'));
            }
            if ($min > $max) {
                throw new LocalizedException(__('Min selection cannot be greater than max selection.'));
            }
        }
    }

    /**
     * @param int $questionId
     * @param string $questionType
     * @param array<string, mixed> $data
     * @throws \Exception
     */
    private function syncQuestionOptions(int $questionId, string $questionType, array $data): void
    {
        $debugFile = BP . '/var/log/customersurvey_upload_debug.log';
        $optionBasedTypes = Question::getOptionBasedTypes();

        $existingCollection = $this->optionCollectionFactory->create();
        $existingCollection->addFieldToFilter('question_id', $questionId);

        if (!in_array($questionType, $optionBasedTypes, true)) {
            foreach ($existingCollection as $existingOption) {
                $this->optionRepository->delete($existingOption);
            }

            return;
        }

        $postedOptions = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
        
        // Use $_FILES directly for more reliable access
        $files = $_FILES['option_images'] ?? [];
        
        $debugMsg = "\n--- syncQuestionOptions ---\n";
        $debugMsg .= "Posted Options Count: " . count($postedOptions) . "\n";
        $debugMsg .= "Files Array: " . print_r($files, true) . "\n";
        $debugMsg .= "Question Type: " . $questionType . "\n";
        file_put_contents($debugFile, $debugMsg, FILE_APPEND);
        
        $this->logger->debug('Sync Options Start', [
            'question_id' => $questionId,
            'question_type' => $questionType,
            'posted_options_count' => count($postedOptions),
            'files_received' => !empty($files)
        ]);
        
        // Ensure files is an array with proper structure
        if (!is_array($files) || empty($files)) {
            $files = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
            file_put_contents($debugFile, "Files array was empty, initialized default\n", FILE_APPEND);
            $this->logger->debug('Files array was empty, initialized default structure');
        }
        
        $keepOptionIds = [];
        $rowIndex = 0;

        foreach ($postedOptions as $formKey => $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string)($row['title'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));
            $existingImage = trim((string)($row['existing_image'] ?? ''));
            $deleteImage = !empty($row['delete_image']);

            // Check for file upload using both the form key and a row counter
            $hasFileUpload = (isset($files['name'][$formKey]) && !empty($files['name'][$formKey])) 
                          || (isset($files['name'][$rowIndex]) && !empty($files['name'][$rowIndex]));
            
            $debugMsg = "\n--- Processing Option ---\n";
            $debugMsg .= "FormKey: $formKey, RowIndex: $rowIndex\n";
            $debugMsg .= "Title: $title, Value: $value\n";
            $debugMsg .= "ExistingImage: $existingImage, DeleteImage: $deleteImage\n";
            $debugMsg .= "HasFileUpload: " . ($hasFileUpload ? 'YES' : 'NO') . "\n";
            $debugMsg .= "File at formKey: " . ($files['name'][$formKey] ?? 'NULL') . "\n";
            $debugMsg .= "File at rowIndex: " . ($files['name'][$rowIndex] ?? 'NULL') . "\n";
            file_put_contents(BP . '/var/log/customersurvey_upload_debug.log', $debugMsg, FILE_APPEND);
            
            $this->logger->debug('Processing option row', [
                'formKey' => $formKey,
                'rowIndex' => $rowIndex,
                'title' => $title,
                'value' => $value,
                'existing_image' => $existingImage,
                'has_file_upload' => $hasFileUpload,
                'file_name_formkey' => $files['name'][$formKey] ?? null,
                'file_name_rowindex' => $files['name'][$rowIndex] ?? null
            ]);
            
            if ($title === '' && $value === '' && $existingImage === '' && !$hasFileUpload) {
                $optionId = isset($row['option_id']) ? (int)$row['option_id'] : 0;
                // If this is an existing option (has option_id), preserve it in keepOptionIds
                if ($optionId > 0) {
                    file_put_contents(BP . '/var/log/customersurvey_upload_debug.log', "PRESERVING: Empty row but has option_id=$optionId\n", FILE_APPEND);
                    $keepOptionIds[] = $optionId;
                } else {
                    file_put_contents(BP . '/var/log/customersurvey_upload_debug.log', "SKIPPING: Empty row with no option_id\n", FILE_APPEND);
                }
                $this->logger->debug('Skipping empty option row', ['optionId' => $optionId]);
                $rowIndex++;
                continue;
            }

            if ($questionType === Question::TYPE_IMAGE_SELECTION && $title !== '' && $value === '') {
                // For image selection, use title as value when admin leaves value empty.
                $value = $title;
            }

            if ($title === '' || $value === '') {
                throw new LocalizedException(__('Each option must have both title and value.'));
            }

            $optionId = isset($row['option_id']) ? (int)$row['option_id'] : 0;
            $option = $optionId ? $this->optionRepository->getById($optionId) : $this->optionFactory->create();

            $imagePath = $deleteImage ? '' : $existingImage;
            
            // Only attempt upload if file is present
            if ($hasFileUpload) {
                try {
                    // Try both the form key and row index
                    $fileIndex = (isset($files['name'][$formKey]) && !empty($files['name'][$formKey])) ? $formKey : $rowIndex;
                    $this->logger->debug('Attempting file upload', ['fileIndex' => $fileIndex]);
                    $uploadedPath = $this->uploadOptionImageByIndex($files, $fileIndex);
                    $this->logger->debug('Upload result', ['uploaded_path' => $uploadedPath]);
                    if (!empty($uploadedPath)) {
                        $imagePath = $uploadedPath;
                    }
                } catch (LocalizedException $e) {
                    // Re-throw to be caught by outer try/catch
                    $this->logger->error('Upload error: ' . $e->getMessage());
                    throw $e;
                }
            }

            $this->logger->debug('Saving option', [
                'title' => $title,
                'value' => $value,
                'image_path' => $imagePath,
                'option_id' => $option->getOptionId()
            ]);

            $option->setQuestionId($questionId);
            $option->setTitle($title);
            $option->setValue($value);
            $option->setSortOrder((int)($row['sort_order'] ?? 0));
            $option->setImage(!empty($imagePath) ? $imagePath : null);

            $this->optionRepository->save($option);
            $this->logger->debug('Option saved', ['option_id' => $option->getOptionId()]);
            $keepOptionIds[] = (int)$option->getOptionId();
            $rowIndex++;
        }

        foreach ($existingCollection as $existingOption) {
            if (!in_array((int)$existingOption->getOptionId(), $keepOptionIds, true)) {
                $this->optionRepository->delete($existingOption);
            }
        }
    }

    /**
     * @param array<string, mixed> $files
     * @param string|int $index
     * @return string
     * @throws LocalizedException
     */
    private function uploadOptionImageByIndex(array $files, $index): string
    {
        $debugFile = BP . '/var/log/customersurvey_upload_debug.log';
        $debugMsg = "\n--- Upload Method Called ---\n";
        $debugMsg .= "Index: " . var_export($index, true) . "\n";
        $debugMsg .= "All Files Keys: " . implode(', ', array_keys($files['name'] ?? [])) . "\n";
        
        // Safely get values with null coalescing
        $fileName = $files['name'][$index] ?? null;
        $tmpName = $files['tmp_name'][$index] ?? null;
        $errorCode = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);

        $debugMsg .= "FileName: " . var_export($fileName, true) . "\n";
        $debugMsg .= "TmpName: " . var_export($tmpName, true) . "\n";
        $debugMsg .= "ErrorCode: $errorCode\n";
        file_put_contents($debugFile, $debugMsg, FILE_APPEND);

        $this->logger->debug('uploadOptionImageByIndex called', [
            'index' => $index,
            'fileName' => $fileName,
            'tmpName' => $tmpName,
            'errorCode' => $errorCode
        ]);

        // Verify file exists at this index
        if (empty($fileName) || empty($tmpName)) {
            $debugMsg2 = "Returning empty: fileName empty=" . empty($fileName) . ", tmpName empty=" . empty($tmpName) . "\n";
            file_put_contents($debugFile, $debugMsg2, FILE_APPEND);
            $this->logger->debug('File name or tmp name is empty, returning empty string');
            return '';
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
                UPLOAD_ERR_CANT_WRITE => 'Cannot write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            ];
            $msg = $errorMessages[$errorCode] ?? 'Unknown upload error';
            $this->logger->error('Upload error detected', ['code' => $errorCode, 'message' => $msg]);
            throw new LocalizedException(__('File upload error: %1 (code: %2)', $msg, $errorCode));
        }

        if (!is_uploaded_file($tmpName)) {
            $this->logger->error('File is not an uploaded file', ['tmpName' => $tmpName]);
            throw new LocalizedException(__('Invalid file upload. File was not properly uploaded.'));
        }

        $this->logger->debug('File validation passed, proceeding with move');

        try {
            $targetDir = 'customersurvey/options';
            $mediaBasePath = BP . '/pub/media';
            $uploadDir = $mediaBasePath . '/' . $targetDir;
            
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            // Validate file extension
            $pathInfo = pathinfo($fileName);
            $ext = strtolower($pathInfo['extension'] ?? '');
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowedExtensions, true)) {
                throw new LocalizedException(
                    __('File type "%1" is not allowed. Allowed types: %2', $ext, implode(', ', $allowedExtensions))
                );
            }

            // Generate safe filename
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $newFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) . '-' . time() . '-' . rand(1000, 9999) . '.' . $ext;
            
            $targetPath = $uploadDir . '/' . $newFileName;
            
            // Move uploaded file
            if (!move_uploaded_file($tmpName, $targetPath)) {
                $this->logger->error('Failed to move uploaded file', ['from' => $tmpName, 'to' => $targetPath]);
                throw new LocalizedException(__('Failed to move uploaded file to target directory.'));
            }

            $this->logger->debug('File moved successfully', ['path' => $targetPath]);

            // Change permissions if needed
            @chmod($targetPath, 0644);

            $finalPath = $targetDir . '/' . $newFileName;
            $this->logger->debug('Upload complete', ['final_path' => $finalPath]);
            file_put_contents(BP . '/var/log/customersurvey_upload_debug.log', "Upload successful: $finalPath\n", FILE_APPEND);
            return $finalPath;
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $exception) {
            throw new LocalizedException(__('Option image upload failed: %1', $exception->getMessage()));
        }
    }
}
