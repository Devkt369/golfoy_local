<?php
namespace ArtM\Delhiveryqc\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthenticationException;

class AuthenticationPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function aroundDispatch($subject, \Closure $proceed, $request)
    {
        // Perform authentication logic here
        $token = $this->request->getHeader('Authorization');

        /*if (!$this->isValidToken($token)) {
            throw new AuthenticationException(__('Invalid tokens'));
        }
*/
        return $proceed($request);
    }

    private function isValidToken($token)
    {
        // Implement token validation logic here
        // Return true if the token is valid, otherwise return false
    }
}
