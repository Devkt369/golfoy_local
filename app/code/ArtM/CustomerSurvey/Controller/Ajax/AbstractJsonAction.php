<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Request\InvalidRequestException;

abstract class AbstractJsonAction extends Action implements CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPayload(): array
    {
        $raw = (string)$this->getRequest()->getContent();
        if ($raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param mixed $data
     */
    protected function jsonResponse($data, int $httpCode = 200): Json
    {
        /** @var Json $result */
        $result = $this->jsonFactory->create();
        $result->setHttpResponseCode($httpCode);
        $result->setData($data);
        return $result;
    }

    protected function errorResponse(\Throwable $e): Json
    {
        $message = $e instanceof LocalizedException
            ? (string)$e->getMessage()
            : 'Customer survey request failed.';

        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
