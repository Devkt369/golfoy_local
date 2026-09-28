<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Data Feed Manager general helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $_framework;
    public $_coreDate = null;
    /**
     * @var array
     */
    protected $_dateFormats = ['{f}', 'Y-m-d-{f}', '{f}-Y-m-d', 'Y-m-d-H-i-s-{f}', '{f}-Y-m-d-H-i-s', 'Y-m-d-H-i-s'];
    /**
     * @var array
     */
    protected $_fieldSeparators = [';', ',', '|', '\\t' => '\\tab', '[|]'];
    /**
     * @var array
     */
    protected $_fieldProtectors = ['"', "'", '' => 'none'];
    /**
     * @var array
     */
    protected $_fieldEscapes = ['"', "\\"];
    public function __construct(\Wyomind\DataFeedManager\Helper\Delegate $wyomind, \Magento\Framework\App\Helper\Context $context)
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context);
    }
    /**
     * @param $format
     * @param $filename
     * @param $updatedAt
     * @return mixed
     */
    public function getFinalFilename($format, $filename, $updatedAt)
    {
        return str_replace('{f}', (string) $filename, date($format, strtotime((string) $updatedAt)));
    }
    /**
     * @param $fn
     * @param $ext
     * @return array
     */
    public function getDateFormats($fn, $ext)
    {
        $toReturn = [];
        foreach ($this->_dateFormats as $dateFormat) {
            $toReturn[] = ['value' => $dateFormat, 'label' => str_replace('###', $fn, $this->_coreDate->date(str_replace('{f}', '###', (string) $dateFormat)) . $ext)];
        }
        return $toReturn;
    }
    /**
     * @param $type
     * @return string
     */
    public function getExtFromType($type)
    {
        switch ($type) {
            case 1:
                $ext = '.xml';
                break;
            case 2:
                $ext = '.txt';
                break;
            case 3:
                $ext = '.csv';
                break;
            case 4:
                $ext = '.tsv';
                break;
            case 5:
                $ext = '.din';
                break;
            default:
                $ext = '.ext';
        }
        return $ext;
    }
    /**
     * @return array
     */
    public function getFileFormats()
    {
        return [['value' => 1, 'label' => 'xml'], ['value' => 2, 'label' => 'txt'], ['value' => 3, 'label' => 'csv'], ['value' => 4, 'label' => 'tsv']];
    }
    /**
     * @return array
     */
    public function getEncodings()
    {
        return [['value' => 'UTF-8', 'label' => 'UTF-8'], ['value' => 'Windows-1252', 'label' => 'Windows-1252 (ANSI)']];
    }
    /**
     * @return array
     */
    public function getYesNoOptions()
    {
        return [['value' => 0, 'label' => __('No')], ['value' => 1, 'label' => __('Yes')]];
    }
    /**
     * @return array
     */
    public function getFieldSeparators()
    {
        $toReturn = [];
        foreach ($this->_fieldSeparators as $key => $separator) {
            if (is_numeric($key)) {
                $toReturn[] = ['value' => $separator, 'label' => $separator];
            } else {
                $toReturn[] = ['value' => $key, 'label' => $separator];
            }
        }
        return $toReturn;
    }
    /**
     * @return array
     */
    public function getFieldProtectors()
    {
        $toReturn = [];
        foreach ($this->_fieldProtectors as $key => $protector) {
            if (is_numeric($key)) {
                $toReturn[] = ['value' => $protector, 'label' => $protector];
            } else {
                $toReturn[] = ['value' => $key, 'label' => $protector];
            }
        }
        return $toReturn;
    }
    /**
     * @return array
     */
    public function getFieldEscapes()
    {
        $toReturn = [];
        foreach ($this->_fieldEscapes as $key => $escape) {
            if (is_numeric($key)) {
                $toReturn[] = ['value' => $escape, 'label' => $escape];
            } else {
                $toReturn[] = ['value' => $key, 'label' => $escape];
            }
        }
        return $toReturn;
    }
    /**
     * @param $text
     * @param string $tags
     * @param bool $invert
     * @return null|string|string[]
     */
    public function stripTagsContent($text, $tags = '', $invert = false)
    {
        preg_match_all('/<(.+?)[\\s]*\\/?[\\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);
        if (is_array($tags) and count($tags) > 0) {
            if ($invert == false) {
                return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\\b)(\\w+)\\b.*?>.*?</\\1>@si', '', $text);
            } else {
                return preg_replace('@<(' . implode('|', $tags) . ')\\b.*?>.*?</\\1>@si', '', $text);
            }
        } elseif ($invert == false) {
            return preg_replace('@<(\\w+)\\b.*?>.*?</\\1>@si', '', $text);
        }
        return strip_tags($text);
    }
    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return mixed
     */
    public function strReplaceFirst($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
    /**
     * Is MSI enabled
     * @return bool
     */
    public function isMsiEnabled()
    {
        $_sourceCollection = null;
        if ($this->_framework->moduleIsEnabled("Magento_Inventory") && $this->_framework->moduleIsEnabled("Magento_InventorySales")) {
            if (class_exists("\\Magento\\Inventory\\Model\\ResourceModel\\Source\\Collection")) {
                $_sourceCollection = \MAgento\Framework\App\ObjectManager::getInstance()->get("\\Magento\\Inventory\\Model\\ResourceModel\\Source\\Collection");
            }
        }
        $sourcesCount = 0;
        if ($_sourceCollection != null) {
            $sourcesCount = $_sourceCollection->getSize();
        }
        return version_compare($this->_framework->getMagentoVersion(), '2.3.0', '>=') && $sourcesCount > 1 && $this->_framework->moduleIsEnabled('Magento_InventorySales');
    }
}