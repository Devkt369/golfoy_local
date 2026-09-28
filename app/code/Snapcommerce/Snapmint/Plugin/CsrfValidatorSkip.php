<?php
namespace Snapcommerce\Snapmint\Plugin;
class CsrfValidatorSkip
{
    /**
     * CsrfValidator to skip validation for razorpay
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/csrfvalidation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(' ------ '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip".' ------ ');
        $logger->info('pos -- '.$request->getOriginalPathInfo() .'  -- '.$request->getModuleName());
 
        if ($request->getModuleName() == 'razorpay') {
            $logger->info('razorpay line 26 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip");
            return; // Skip CSRF check
        }

        if ($request->getModuleName() == 'snapmint') {
            $logger->info('snapmint line 30 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip");
            return; // Skip CSRF check
        }

        if ($request->getModuleName() == 'payment') {
            $logger->info('jsupay line 35 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip");
            return; // Skip CSRF check
        }

        if (strpos($request->getOriginalPathInfo(), 'snapmint/ipn/callbacksnapmint') !== false) {
            $logger->info('other line 40 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip");
            return; // Skip CSRF check
        }

        if (strpos($request->getOriginalPathInfo(), 'snapmint/checkout/redirect') !== false) {
            $logger->info('other line 46 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip");
            return; // Skip CSRF check
        }


        $logger->info('main 51 '."\Snapcommerce\Snapmint\Plugin\CsrfValidatorSkip ". $request->getModuleName());


        $logger->info(' ------ ----------------------- ');

        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
}
