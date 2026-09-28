<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Ajax;

use ArtM\CustomerSurvey\Api\SurveyEngineInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Answered extends AbstractJsonAction
{
    /**
     * @var SurveyEngineInterface
     */
    private $surveyEngine;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SurveyEngineInterface $surveyEngine
    ) {
        parent::__construct($context, $jsonFactory);
        $this->surveyEngine = $surveyEngine;
    }

    public function execute()
    {
        try {
            return $this->jsonResponse([
                'success' => $this->surveyEngine->trackAnswered($this->getPayload()),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }
}