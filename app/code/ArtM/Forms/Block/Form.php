<?php
namespace ArtM\Forms\Block;

use Magento\Framework\View\Element\Template;
use ArtM\Forms\Model\FormsFactory;

class Form extends Template
{
    protected $formsFactory;

    public function __construct(
        Template\Context $context,
        FormsFactory $formsFactory,
        array $data = []
    ) {
        $this->formsFactory = $formsFactory;
        parent::__construct($context, $data);
    }

    public function getFormByIdentifier($identifier)
    {
        $form = $this->formsFactory->create()->getCollection()
            ->addFieldToFilter('form_identifier', $identifier)
            ->getFirstItem();
        if ($form && $form->getId()) {
            return $form;
        }
        return false;
    }
}
