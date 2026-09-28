<?php
declare(strict_types=1);

namespace Magestore\Logger\Model\Structure\Config;

/**
 * Class Converter
 *
 * Converter of attributes configuration from \DOMDocument to array
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $result = [];
        /** @var DOMNode $groupNode */
        foreach ($source->documentElement->childNodes as $groupNode) {
            if ($groupNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $groupName = $groupNode->attributes->getNamedItem('name')->nodeValue;
            /** @var DOMNode $groupAttributeNode */
            foreach ($groupNode->childNodes as $groupAttributeNode) {
                if ($groupAttributeNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $result[$groupName][$groupAttributeNode->attributes->getNamedItem('name')->nodeValue] = [
                    'name' => $groupAttributeNode->attributes->getNamedItem('name')->nodeValue,
                    'required' => $groupAttributeNode->attributes->getNamedItem('required')->nodeValue,
                    'type' => $groupAttributeNode->attributes->getNamedItem('type')->nodeValue,
                    'sortOrder' => $groupAttributeNode->attributes->getNamedItem('sortOrder')->nodeValue
                ];
            }
        }
        return $result;
    }
}
