<?php
namespace ArtM\AdminAI\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class GeminiClient
{
    public const XML_PATH_API_KEY = 'adminai/general/api_key';
    private const API_ENDPOINT_TEMPLATE = 'https://generativelanguage.googleapis.com/v1/models/%s:generateContent';
    private const MODEL_CANDIDATES = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-001',
        'gemini-2.0-flash-exp-preview-04-15',
        'gemini-1.5-flash-latest',
        'gemini-1.5-pro-latest',
        'gemini-1.5-flash-001',
        'gemini-1.5-pro'
    ];
    private const DEFAULT_ERROR_MESSAGE = 'Unable to get a response from Gemini right now.';
    private const SYSTEM_PROMPT = "You are a Magento admin assistant.\nYou MUST return ONLY valid JSON.\nNever explain anything.\n\nSupported intents:\n\n1. low_stock - for questions about low stock products\n2. product_qty - for questions about stock quantity of specific SKU\n3. sales_today - for questions about today's sales\n4. trending_products - for questions about trending products\n5. general_chat - for any other questions\n\nExamples:\n\nUser: what stock is low\nResponse:\n{\"intent\":\"low_stock\"}\n\nUser: qty of sku ABC123\nResponse:\n{\"intent\":\"product_qty\",\"sku\":\"ABC123\"}\n\nUser: sale today\nResponse:\n{\"intent\":\"sales_today\"}\n\nUser: is this a configurable product?\nResponse:\n{\"intent\":\"general_chat\",\"message\":\"Yes, this product type can be configured with different options and attributes.\"}\n\nFor ANY question not matching other intents, ALWAYS respond with general_chat and include a helpful message.\nReturn ONLY JSON. Never add text outside JSON.";

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer,
        LoggerInterface $logger,
        EncryptorInterface $encryptor
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $message
     * @return array
     */
    public function askGemini(string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            return $this->fallbackResponse('Message cannot be empty.');
        }

        $storedApiKey = trim((string)$this->scopeConfig->getValue(self::XML_PATH_API_KEY));
        $apiKey = $storedApiKey;

        if ($storedApiKey !== '') {
            try {
                $decryptedApiKey = trim((string)$this->encryptor->decrypt($storedApiKey));

                if ($decryptedApiKey !== '') {
                    $apiKey = $decryptedApiKey;
                }
            } catch (\Throwable $exception) {
                // If value is plain text (or decrypt fails), keep using raw value.
                $this->logger->warning(
                    'ArtM_AdminAI Gemini API key decryption failed; using raw config value.',
                    ['exception_class' => get_class($exception)]
                );
            }
        }

        if ($apiKey === '') {
            $this->logger->warning('ArtM_AdminAI Gemini API key is not configured.');

            return $this->fallbackResponse('Gemini API key is not configured.');
        }

        // Keep payload compatible across Gemini API/model variants.
        // Some endpoints reject systemInstruction/responseMimeType fields.
        $requestBody = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => self::SYSTEM_PROMPT . "\n\nUser message: " . $message]
                    ]
                ]
            ]
        ];

        foreach (self::MODEL_CANDIDATES as $model) {
            try {
                $this->curl->addHeader('Content-Type', 'application/json');
                $this->curl->addHeader('Accept', 'application/json');
                $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
                $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
                $this->curl->setTimeout(30);
                $this->curl->post(
                    sprintf(self::API_ENDPOINT_TEMPLATE, rawurlencode($model)) . '?key=' . rawurlencode($apiKey),
                    $this->jsonSerializer->serialize($requestBody)
                );

                $statusCode = (int)$this->curl->getStatus();
                $responseBody = (string)$this->curl->getBody();

                if ($statusCode < 200 || $statusCode >= 300) {
                    if ($statusCode === 404) {
                        $this->logger->warning(
                            'ArtM_AdminAI Gemini model unavailable, trying next model.',
                            [
                                'model' => $model,
                                'status' => $statusCode,
                                'response' => $responseBody
                            ]
                        );

                        continue;
                    }

                    if ($this->isApiKeyInvalidResponse($responseBody)) {
                        $this->logger->error(
                            'ArtM_AdminAI Gemini API key is invalid.',
                            [
                                'model' => $model,
                                'status' => $statusCode,
                                'response' => $responseBody
                            ]
                        );

                        return $this->fallbackResponse('Gemini API key is invalid.');
                    }

                    $this->logger->error(
                        'ArtM_AdminAI Gemini API request failed.',
                        [
                            'model' => $model,
                            'status' => $statusCode,
                            'response' => $responseBody
                        ]
                    );

                    return $this->fallbackResponse();
                }

                $responseData = $this->jsonSerializer->unserialize($responseBody);
                $parts = $responseData['candidates'][0]['content']['parts'] ?? [];

                if (!is_array($parts)) {
                    $this->logger->warning(
                        'ArtM_AdminAI Gemini API returned an unexpected payload.',
                        [
                            'model' => $model,
                            'response' => $responseData
                        ]
                    );

                    continue;
                }

                $replyParts = [];

                foreach ($parts as $part) {
                    if (is_array($part) && !empty($part['text'])) {
                        $replyParts[] = trim((string)$part['text']);
                    }
                }

                $reply = trim(implode("\n", array_filter($replyParts)));

                if ($reply !== '') {
                    $decodedReply = $this->decodeJsonReply($reply);

                    if (is_array($decodedReply)) {
                        return $decodedReply;
                    }

                    $this->logger->warning(
                        'ArtM_AdminAI Gemini returned non-JSON content despite JSON instruction.',
                        [
                            'model' => $model,
                            'reply' => $reply
                        ]
                    );

                    return $this->fallbackResponse($reply);
                }

                $this->logger->warning(
                    'ArtM_AdminAI Gemini API returned no text reply.',
                    [
                        'model' => $model,
                        'response' => $responseData
                    ]
                );
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'ArtM_AdminAI Gemini client exception: ' . $exception->getMessage(),
                    [
                        'model' => $model,
                        'exception_class' => get_class($exception),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ]
                );

                return $this->fallbackResponse();
            }
        }

        $this->logger->error(
            'ArtM_AdminAI could not find a supported Gemini model for this API key.',
            ['models' => self::MODEL_CANDIDATES]
        );

        return $this->fallbackResponse();
    }

    /**
     * @param string $reply
     * @return array|null
     */
    private function decodeJsonReply(string $reply): ?array
    {
        $candidateReplies = [trim($reply)];

        $cleanReply = preg_replace('/^```(?:json)?\s*/i', '', $candidateReplies[0]);
        $cleanReply = preg_replace('/\s*```$/', '', (string)$cleanReply);

        if (is_string($cleanReply) && $cleanReply !== '') {
            $candidateReplies[] = trim($cleanReply);

            $startPos = strpos($cleanReply, '{');
            $endPos = strrpos($cleanReply, '}');

            if ($startPos !== false && $endPos !== false && $endPos >= $startPos) {
                $candidateReplies[] = substr($cleanReply, $startPos, $endPos - $startPos + 1);
            }
        }

        foreach ($candidateReplies as $candidateReply) {
            if (!is_string($candidateReply) || $candidateReply === '') {
                continue;
            }

            try {
                $decoded = $this->jsonSerializer->unserialize($candidateReply);

                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\InvalidArgumentException $exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param string $message
     * @return array
     */
    private function fallbackResponse(string $message = self::DEFAULT_ERROR_MESSAGE): array
    {
        return [
            'intent' => 'general_chat',
            'message' => $message
        ];
    }

    /**
     * @param string $responseBody
     * @return bool
     */
    private function isApiKeyInvalidResponse(string $responseBody): bool
    {
        if (stripos($responseBody, 'API_KEY_INVALID') !== false) {
            return true;
        }

        if (stripos($responseBody, 'API key not valid') !== false) {
            return true;
        }

        return false;
    }
}