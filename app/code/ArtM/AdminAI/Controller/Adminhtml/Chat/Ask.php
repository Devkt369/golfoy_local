<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Chat;

use ArtM\AdminAI\Model\ChatHistoryService;
use ArtM\AdminAI\Model\GeminiClient;
use ArtM\AdminAI\Model\IntentProcessor;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Ask extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var GeminiClient
     */
    private $geminiClient;

    /**
     * @var IntentProcessor
     */
    private $intentProcessor;

    /**
        * @var ChatHistoryService
     */
        private $chatHistoryService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GeminiClient $geminiClient
     * @param IntentProcessor $intentProcessor
     * @param ChatHistoryService $chatHistoryService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        GeminiClient $geminiClient,
        IntentProcessor $intentProcessor,
        ChatHistoryService $chatHistoryService,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->geminiClient = $geminiClient;
        $this->intentProcessor = $intentProcessor;
        $this->chatHistoryService = $chatHistoryService;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $message = trim((string)$this->getRequest()->getParam('message'));
        $sessionId = (int)$this->getRequest()->getParam('session_id', 0);
        $geminiEnabled = filter_var(
            $this->getRequest()->getParam('gemini_enabled', 0),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($geminiEnabled === null) {
            $geminiEnabled = false;
        }

        if ($message === '') {
            return $result->setData([
                'success' => false,
                'session_id' => $sessionId,
                'reply' => [
                    'message' => 'Message cannot be empty.'
                ]
            ]);
        }

        $adminUser = $this->_auth ? $this->_auth->getUser() : null;
        $adminUserId = $adminUser ? (int)$adminUser->getId() : 0;
        $adminUsername = $adminUser ? (string)$adminUser->getUserName() : '';
        $adminName = $adminUser ? trim((string)$adminUser->getFirstName() . ' ' . (string)$adminUser->getLastName()) : '';

        if ($sessionId <= 0 || !$this->chatHistoryService->sessionExists($sessionId, $adminUserId)) {
            $sessionId = $this->chatHistoryService->createSession(null, $adminUserId, $adminUsername, $adminName);
        }

        $offlineIntentData = $this->buildOfflineIntentData($message);
        $userMessageDisplay = $this->buildUserIntentSummaryMessage($message, $offlineIntentData);

        $this->chatHistoryService->appendMessage($sessionId, 'user', $userMessageDisplay);
        $this->chatHistoryService->autoTitleIfDefault($sessionId, $message);

        try {
            $isExactIntent = ($offlineIntentData['intent'] ?? 'general_chat') !== 'general_chat';

            if ($isExactIntent) {
                // Token saver: if user already entered exact intent format, skip Gemini even in Gemini mode.
                $intentData = $offlineIntentData;

                if (!empty($intentData['message'])) {
                    $assistantMessage = (string)$intentData['message'];
                    $this->chatHistoryService->appendMessage($sessionId, 'assistant', $assistantMessage);

                    return $result->setData([
                        'success' => true,
                        'session_id' => $sessionId,
                        'user_message_display' => $userMessageDisplay,
                        'reply' => [
                            'intent' => $intentData['intent'] ?? 'general_chat',
                            'message' => $assistantMessage
                        ]
                    ]);
                }
            } elseif ($geminiEnabled) {
                $intentData = $this->geminiClient->askGemini($message);
            } else {
                $intentData = $offlineIntentData;

                if (!empty($intentData['message'])) {
                    $assistantMessage = (string)$intentData['message'];
                    $this->chatHistoryService->appendMessage($sessionId, 'assistant', $assistantMessage);

                    return $result->setData([
                        'success' => true,
                        'session_id' => $sessionId,
                        'user_message_display' => $userMessageDisplay,
                        'reply' => [
                            'intent' => $intentData['intent'] ?? 'general_chat',
                            'message' => $assistantMessage
                        ]
                    ]);
                }
            }

            if (!is_array($intentData)) {
                $intentData = [
                    'intent' => 'general_chat',
                    'message' => 'Invalid response format received.'
                ];
            }

            // Process the intent and get formatted response
            $assistantMessage = $this->intentProcessor->process($intentData);
            $this->chatHistoryService->appendMessage($sessionId, 'assistant', $assistantMessage);

            return $result->setData([
                'success' => true,
                'session_id' => $sessionId,
                'user_message_display' => $userMessageDisplay,
                'reply' => [
                    'intent' => $intentData['intent'] ?? 'general_chat',
                    'message' => $assistantMessage
                ]
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Chat Ask error', ['exception' => $e]);

            $assistantMessage = 'An error occurred. Please try again.';
            $this->chatHistoryService->appendMessage($sessionId, 'assistant', $assistantMessage);

            return $result->setData([
                'success' => true,
                'session_id' => $sessionId,
                'user_message_display' => $userMessageDisplay,
                'reply' => [
                    'intent' => 'general_chat',
                    'message' => $assistantMessage
                ]
            ]);
        }
    }

    /**
     * Build readable user-side command summary for intent commands.
     *
     * @param string $rawMessage
     * @param array $intentData
     * @return string
     */
    private function buildUserIntentSummaryMessage(string $rawMessage, array $intentData): string
    {
        $intent = (string)($intentData['intent'] ?? 'general_chat');
        if ($intent === 'general_chat') {
            return $rawMessage;
        }

        $params = [];
        foreach ($intentData as $key => $value) {
            if (in_array($key, ['intent', 'message', 'reply'], true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $paramLabel = $this->humanizeIntentParamLabel($intent, (string)$key);
            $params[] = $paramLabel . ': ' . (is_scalar($value) ? (string)$value : json_encode($value));
        }

        $intentLine = 'Query for ' . str_replace('_', ' ', $intent) . ' intent:';
        if (empty($params)) {
            return $rawMessage . "\n" . $intentLine;
        }

        return $rawMessage . "\n" . $intentLine . "\n" . implode("\n", $params);
    }

    /**
     * @param string $intent
     * @param string $paramKey
     * @return string
     */
    private function humanizeIntentParamLabel(string $intent, string $paramKey): string
    {
        if ($intent === 'customer_search' && $paramKey === 'query') {
            return 'name';
        }

        return str_replace('_', ' ', $paramKey);
    }

    /**
     * Offline mode intent parser using exact command words.
     *
     * @param string $message
     * @return array
     */
    private function buildOfflineIntentData(string $message): array
    {
        // Remove unresolved template placeholders like {from_date} so command still works.
        $normalized = preg_replace('/\{[^}]+\}/', ' ', (string)$message);
        $normalized = trim((string)$normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim((string)$normalized);
        $lowerMessage = strtolower($normalized);

        if ($lowerMessage === 'low_stock') {
            return [
                'intent' => 'low_stock',
                'min_qty' => 0,
                'max_qty' => 10
            ];
        }

        if (preg_match('/^low_stock(?:\s+|\s*:\s*)(\d+)?(?:\s+(\d+))?$/i', $normalized, $matches)) {
            $firstNum = isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : null;
            $secondNum = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : null;

            if ($secondNum !== null) {
                $minQty = min($firstNum, $secondNum);
                $maxQty = max($firstNum, $secondNum);
            } else {
                $minQty = $firstNum !== null ? 0 : 0;
                $maxQty = $firstNum !== null ? $firstNum : 10;
            }

            return [
                'intent' => 'low_stock',
                'min_qty' => $minQty,
                'max_qty' => max(1, $maxQty)
            ];
        }

        if ($lowerMessage === 'product_qty') {
            return [
                'intent' => 'product_qty',
                'message' => "Please provide SKU or product ID. Use: product_qty ABC123\n[[INPUT:product_qty|Enter SKU or Product ID|Send]]"
            ];
        }

        if (preg_match('/^product_qty(?:\s+|\s*:\s*)(.+)$/i', $normalized, $matches)) {
            return [
                'intent' => 'product_qty',
                'sku' => trim((string)$matches[1])
            ];
        }

        if ($lowerMessage === 'trending_products') {
            return [
                'intent' => 'trending_products',
                'limit' => 10
            ];
        }

        if (preg_match('/^trending_products(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $parts = $remaining === '' ? [] : preg_split('/\s+/', $remaining);

            $limit = 10;
            $fromDate = null;
            $toDate = null;
            $attributeSets = '';
            $attributeFilters = '';

            if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                $limit = (int)array_shift($parts);
            }

            if (!empty($parts) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$parts[0])) {
                $fromDate = (string)array_shift($parts);
            }

            if (!empty($parts) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$parts[0])) {
                $toDate = (string)array_shift($parts);
            }

            if (!empty($parts)) {
                $attributeSets = (string)array_shift($parts);
            }

            if (!empty($parts)) {
                $attributeFilters = implode(' ', $parts);
            }

            return [
                'intent' => 'trending_products',
                'limit' => $limit,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'attribute_sets' => $attributeSets,
                'attribute_filters' => $attributeFilters
            ];
        }

        if ($lowerMessage === 'catalog_product_data') {
            return [
                'intent' => 'catalog_product_data',
                'message' => "Please provide SKU/ID and attributes. Use: catalog_product_data GOLTEMXN100SP name,price,status\n[[PREFILL:catalog_product_data |Enter product identifier and attributes]]"
            ];
        }

        if (preg_match('/^catalog_product_data(?:\s+|\s*:\s*)(\S+)(?:\s+(.+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'catalog_product_data',
                'sku_or_id' => trim((string)$matches[1]),
                'attributes' => trim((string)($matches[2] ?? ''))
            ];
        }

        if ($lowerMessage === 'category_search_by_name') {
            return [
                'intent' => 'category_search_by_name',
                'message' => "Please provide category name. Use: category_search_by_name balls\n[[PREFILL:category_search_by_name |Enter category keyword]]"
            ];
        }

        if (preg_match('/^category_search_by_name(?:\s+|\s*:\s*)(.+)$/i', $normalized, $matches)) {
            return [
                'intent' => 'category_search_by_name',
                'name_query' => trim((string)$matches[1])
            ];
        }

        if ($lowerMessage === 'category_data_by_id') {
            return [
                'intent' => 'category_data_by_id',
                'message' => "Please provide category id and attributes. Use: category_data_by_id 12 description,page_layout\n[[PREFILL:category_data_by_id |Enter category id and attributes]]"
            ];
        }

        if (preg_match('/^category_data_by_id(?:\s+|\s*:\s*)(\d+)(?:\s+(.+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'category_data_by_id',
                'category_id' => (int)$matches[1],
                'attributes' => trim((string)($matches[2] ?? ''))
            ];
        }

        if (preg_match('/^reserved_products(?:\s+|\s*:\s*)([A-Za-z0-9_\-\/\.]+)?(?:\s+(\d+))?$/i', $normalized, $matches)) {
            $firstArg  = isset($matches[1]) ? trim($matches[1]) : '';
            $secondArg = isset($matches[2]) ? trim($matches[2]) : '';

            // If first arg is purely numeric treat it as limit, not SKU
            if ($firstArg !== '' && ctype_digit($firstArg)) {
                return [
                    'intent' => 'reserved_products',
                    'limit' => (int)$firstArg
                ];
            }

            return [
                'intent' => 'reserved_products',
                'sku'   => $firstArg,
                'limit' => $secondArg !== '' ? (int)$secondArg : 10
            ];
        }

        if ($lowerMessage === 'sales_on_date') {
            return [
                'intent' => 'sales_on_date',
                'message' => "Please provide a date. Use: sales_on_date 2026-03-20\n[[PREFILL:sales_on_date |Pick date and send]]"
            ];
        }

        if (preg_match('/^sales_on_date(?:\s+|\s*:\s*)(\d{4}-\d{2}-\d{2})$/i', $normalized, $matches)) {
            return [
                'intent' => 'sales_on_date',
                'date' => $matches[1]
            ];
        }

        if ($lowerMessage === 'sales_date_range') {
            return [
                'intent' => 'sales_date_range'
            ];
        }

        if ($lowerMessage === 'sales_filtered') {
            return [
                'intent' => 'sales_filtered'
            ];
        }

        if (preg_match('/^sales_date_range(?:\s+|\s*:\s*)(\d{4}-\d{2}-\d{2})\s+(\d{4}-\d{2}-\d{2})$/i', $normalized, $matches)) {
            return [
                'intent' => 'sales_date_range',
                'from_date' => $matches[1],
                'to_date' => $matches[2]
            ];
        }

        if (preg_match('/^sales_filtered(?:\s+|\s*:\s*)(\d{4}-\d{2}-\d{2})?\s*(\d{4}-\d{2}-\d{2})?\s*(\S+)?\s*(.*)$/i', $normalized, $matches)) {
            return [
                'intent' => 'sales_filtered',
                'from_date' => trim((string)($matches[1] ?? '')),
                'to_date' => trim((string)($matches[2] ?? '')),
                'attribute_sets' => trim((string)($matches[3] ?? '')),
                'attribute_filters' => trim((string)($matches[4] ?? ''))
            ];
        }

        if (preg_match('/^reviews_recent(?:\s+|\s*:\s*)(\d+)?(?:\s+(\d+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'reviews_recent',
                'days' => isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 10,
                'limit' => isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : 10
            ];
        }

        if (preg_match('/^products_most_reviews(?:\s+|\s*:\s*)(\d+)?$/i', $normalized, $matches)) {
            return [
                'intent' => 'products_most_reviews',
                'limit' => isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 10
            ];
        }

        if (preg_match('/^products_highest_reviews(?:\s+|\s*:\s*)(\d+)?$/i', $normalized, $matches)) {
            return [
                'intent' => 'products_highest_reviews',
                'limit' => isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 10
            ];
        }

        if ($lowerMessage === 'customer_search') {
            return [
                'intent' => 'customer_search',
                'message' => "Please provide customer query. Use: customer_search mohit makhija\n[[PREFILL:customer_search |Enter customer name/email]]"
            ];
        }

        if (preg_match('/^customer_search(?:\s+|\s*:\s*)(.+)$/i', $normalized, $matches)) {
            return [
                'intent' => 'customer_search',
                'query' => trim((string)$matches[1])
            ];
        }

        if ($lowerMessage === 'customer_details') {
            return [
                'intent' => 'customer_details',
                'message' => "Please provide customer id and optional attributes. Use: customer_details 123 firstname,email\n[[PREFILL:customer_details |Enter customer id and optional attributes]]"
            ];
        }

        if (preg_match('/^customer_details(?:\s+|\s*:\s*)(\d+)(?:\s+(.+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'customer_details',
                'customer_id' => (int)$matches[1],
                'attributes' => trim((string)($matches[2] ?? ''))
            ];
        }

        if ($lowerMessage === 'customer_addresses') {
            return [
                'intent' => 'customer_addresses',
                'message' => "Please provide customer id. Use: customer_addresses 123\n[[PREFILL:customer_addresses |Enter customer id]]"
            ];
        }

        if (preg_match('/^customer_addresses(?:\s+|\s*:\s*)(\d+)$/i', $normalized, $matches)) {
            return [
                'intent' => 'customer_addresses',
                'customer_id' => (int)$matches[1]
            ];
        }

        if (preg_match('/^customers_by_location(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $city = '';
            $region = '';
            $country = '';
            $limit = 20;

            if ($remaining !== '') {
                // Support key/value syntax like: city=chandigarh region=punjab country=IN limit=100
                if (strpos($remaining, '=') !== false) {
                    $parts = preg_split('/\s+/', $remaining) ?: [];
                    foreach ($parts as $part) {
                        if (strpos($part, '=') === false) {
                            continue;
                        }

                        [$key, $value] = array_map('trim', explode('=', $part, 2));
                        $key = strtolower($key);
                        if ($value === '') {
                            continue;
                        }

                        if ($key === 'city') {
                            $city = $value;
                        } elseif ($key === 'region' || $key === 'state') {
                            $region = $value;
                        } elseif ($key === 'country') {
                            $country = strtoupper($value);
                        } elseif ($key === 'limit' && preg_match('/^\d+$/', $value)) {
                            $limit = (int)$value;
                        }
                    }
                } else {
                    $parts = preg_split('/\s+/', $remaining) ?: [];

                    // If last token is numeric, treat it as limit.
                    if (!empty($parts) && preg_match('/^\d+$/', (string)end($parts))) {
                        $limit = (int)array_pop($parts);
                    }

                    if (!empty($parts)) {
                        $city = (string)array_shift($parts);
                    }
                    if (!empty($parts)) {
                        $region = (string)array_shift($parts);
                    }
                    if (!empty($parts)) {
                        $country = strtoupper((string)array_shift($parts));
                    }
                }
            }

            return [
                'intent' => 'customers_by_location',
                'city' => trim($city),
                'region' => trim($region),
                'country' => trim($country),
                'limit' => $limit
            ];
        }

        if ($lowerMessage === 'customer_risk_summary') {
            return [
                'intent' => 'customer_risk_summary'
            ];
        }

        if (preg_match('/^customer_risk_summary(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $riskLevel = '';
            $limit = null;
            $storeId = null;

            if ($remaining !== '') {
                $parts = preg_split('/\s+/', $remaining) ?: [];

                if (!empty($parts) && !preg_match('/^\d+$/', (string)$parts[0])) {
                    $riskLevel = (string)array_shift($parts);
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $limit = (int)array_shift($parts);
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $storeId = (int)array_shift($parts);
                }
            }

            return [
                'intent' => 'customer_risk_summary',
                'risk_level' => $riskLevel,
                'limit' => $limit,
                'store_id' => $storeId
            ];
        }

        if ($lowerMessage === 'stock_projection') {
            return [
                'intent' => 'stock_projection'
            ];
        }

        if (preg_match('/^stock_projection(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $daysAhead = null;
            $sku = '';
            $limit = null;

            if ($remaining !== '') {
                $parts = preg_split('/\s+/', $remaining) ?: [];

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $daysAhead = (int)array_shift($parts);
                }

                if (!empty($parts)) {
                    if (count($parts) === 1 && preg_match('/^\d+$/', (string)$parts[0])) {
                        $limit = (int)array_shift($parts);
                    } else {
                        $sku = (string)array_shift($parts);
                    }
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $limit = (int)array_shift($parts);
                }
            }

            return [
                'intent' => 'stock_projection',
                'days_ahead' => $daysAhead,
                'sku' => $sku,
                'limit' => $limit
            ];
        }

        if ($lowerMessage === 'product_performance') {
            return [
                'intent' => 'product_performance',
                'message' => "Please provide from and to dates. Use: product_performance 2026-01-01 2026-03-27 100 1\n[[PREFILL:product_performance |Enter dates and optional filters]]"
            ];
        }

        if (preg_match('/^product_performance(?:\s+|\s*:\s*)(\d{4}-\d{2}-\d{2})\s+(\d{4}-\d{2}-\d{2})(?:\s+(\d+))?(?:\s+(\d+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'product_performance',
                'from_date' => (string)$matches[1],
                'to_date' => (string)$matches[2],
                'limit' => isset($matches[3]) && $matches[3] !== '' ? (int)$matches[3] : null,
                'store_id' => isset($matches[4]) && $matches[4] !== '' ? (int)$matches[4] : null
            ];
        }

        if ($lowerMessage === 'executive_sales_summary') {
            return [
                'intent' => 'executive_sales_summary',
                'period' => '30d'
            ];
        }

        if (preg_match('/^executive_sales_summary(?:\s+|\s*:\s*)(7d|30d|90d)(?:\s+(\d+))?$/i', $normalized, $matches)) {
            return [
                'intent' => 'executive_sales_summary',
                'period' => strtolower((string)$matches[1]),
                'store_id' => isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : null
            ];
        }

        if ($lowerMessage === 'inactive_customers') {
            return [
                'intent' => 'inactive_customers'
            ];
        }

        if (preg_match('/^inactive_customers(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $inactiveDays = null;
            $limit = null;
            $storeId = null;

            if ($remaining !== '') {
                $parts = preg_split('/\s+/', $remaining) ?: [];

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $inactiveDays = (int)array_shift($parts);
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $limit = (int)array_shift($parts);
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $storeId = (int)array_shift($parts);
                }
            }

            return [
                'intent' => 'inactive_customers',
                'inactive_days' => $inactiveDays,
                'limit' => $limit,
                'store_id' => $storeId
            ];
        }

        if ($lowerMessage === 'customer_segment_list') {
            return [
                'intent' => 'customer_segment_list'
            ];
        }

        if (preg_match('/^customer_segment_list(?:\s+|\s*:\s*)(.*)$/i', $normalized, $matches)) {
            $remaining = trim((string)($matches[1] ?? ''));
            $segmentCode = '';
            $limit = null;
            $storeId = null;

            if ($remaining !== '') {
                $parts = preg_split('/\s+/', $remaining) ?: [];

                if (!empty($parts)) {
                    if (preg_match('/^\d+$/', (string)$parts[0])) {
                        $limit = (int)array_shift($parts);
                    } else {
                        $segmentCode = strtoupper((string)array_shift($parts));
                    }
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $limit = (int)array_shift($parts);
                }

                if (!empty($parts) && preg_match('/^\d+$/', (string)$parts[0])) {
                    $storeId = (int)array_shift($parts);
                }
            }

            return [
                'intent' => 'customer_segment_list',
                'segment_code' => $segmentCode,
                'limit' => $limit,
                'store_id' => $storeId
            ];
        }

        if (in_array($lowerMessage, $this->intentProcessor->getSupportedIntentCodes(), true)) {
            return ['intent' => $lowerMessage];
        }

        return [
            'intent' => 'general_chat',
            'message' => 'System offline, Please use exact words.'
        ];
    }
}