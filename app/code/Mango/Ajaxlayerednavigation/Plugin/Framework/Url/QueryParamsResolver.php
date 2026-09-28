<?php
namespace Mango\Ajaxlayerednavigation\Plugin\Framework\Url;

class QueryParamsResolver
{
    /**
     * {@inheritdoc}
     */
    public function beforeSetQueryParam(\Magento\Framework\Url\QueryParamsResolver $subject, $key, $data)
    {
        if (in_array($key, ['ajaxlist', 'ajax_request', 'reload_navigation', '_'])) {
            $data = null;
        }
        return [ $key, $data];
    }
}
