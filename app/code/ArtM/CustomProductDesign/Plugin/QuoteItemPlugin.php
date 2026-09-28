<?php

namespace ArtM\CustomProductDesign\Plugin;

/**
 * Plugin for Magento Quote Item to handle custom design pricing logic.
 *
 * This plugin calculates and sets the custom price for a quote item based on custom design data.
 * It parses JSON objects from the 'custom_design_userdata_ids' field, sums up the 'total_price' values,
 * and adjusts the item's price accordingly.
 *
 * @category  ArtM
 * @package   ArtM_CustomProductDesign
 */
class QuoteItemPlugin
{
    /**
     * Before save plugin for Quote Item.
     *
     * @param \Magento\Quote\Model\Quote\Item $subject The quote item being saved.
     * @return null
     */
    public function beforeSave(\Magento\Quote\Model\Quote\Item $subject)
    {
        $rawData = $subject->getData('custom_design_userdata_ids');

        if ($rawData) {
            // Attempt to extract all JSON objects inside the string (assuming they are concatenated)
            // We'll extract all substrings that look like JSON objects with a regex
            preg_match_all('/\{.*?\}(?=\\{|$)/s', $rawData, $matches);

            $totalCustomPrice = 0;

            if (!empty($matches[0])) {
                foreach ($matches[0] as $jsonStr) {
                    $customPrintData = json_decode($jsonStr, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Check for total_price in the decoded data
                        if (isset($customPrintData['additional_data'])) {
                            // additional_data itself is JSON string, decode it
                            $additionalData = json_decode($customPrintData['additional_data'], true);
                            if (json_last_error() === JSON_ERROR_NONE && !empty($additionalData['total_price'])) {
                                $totalCustomPrice += (float) $additionalData['total_price'];
                            }
                        } elseif (!empty($customPrintData['total_price'])) {
                            // fallback check if total_price exists at root level
                            $totalCustomPrice += (float) $customPrintData['total_price'];
                        }
                    }
                }
            }

            if ($totalCustomPrice > 0) {
                $specialPrice = $subject->getProduct()->getSpecialPrice();
                // $specialPrice = $subject->getProduct()->getData('quote_item_row_total_incl_tax');
                if($specialPrice > 0){
                    $finalPrice = $specialPrice + $totalCustomPrice;
                    $subject->setCustomPrice($finalPrice);
                    $subject->setOriginalCustomPrice($finalPrice);
                    $subject->getProduct()->setIsSuperMode(true);
                }else{
                    $originalPrice = $subject->getProduct()->getPrice();
                    $finalPrice = $originalPrice + $totalCustomPrice;
                    $subject->setCustomPrice($finalPrice);
                    $subject->setOriginalCustomPrice($finalPrice);
                    $subject->getProduct()->setIsSuperMode(true);
                }
            }
        }

    }
}
