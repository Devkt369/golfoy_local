<?php
/**
 * @author   : Daan van den Bergh
 * @url      : https://daan.dev
 * @package  : Dan0sz/ResourceHints
 * @copyright: (c) 2019 Daan van den Bergh
 */

namespace ArtM\Common\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class Builder implements ObserverInterface
{
    /** @var PageConfig $pageConfig */
    private $pageConfig;

    /**
     * Builder constructor.
     *
     * @param PageConfig  $pageConfig
     */
    public function __construct(
        PageConfig $pageConfig
    ) {
        $this->pageConfig  = $pageConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $resourceHints = [
            'facebookconnect' => [
                'resource' => 'https://connect.facebook.net',
                'type'     => 'preconnect',
                ],
            'googletag' => [
                'resource' => 'https://www.googletagmanager.com',
                'type'     => 'preconnect',
                ],
            'klevu' => [
                'resource' => 'https://js.klevu.com',
                'type'     => 'preconnect',
                ],
            'cdngolfoy' => [
                'resource' => 'https://cdn.golfoy.com',
                'type'     => 'preconnect',
                ],
            'diffuser' => [
                'resource' => 'https://diffuser-cdn.app-us1.com',
                'type'     => 'preconnect',
            ],
            'snap' => [
                'resource' => 'https://snap.licdn.com',
                'type'     => 'preconnect'
                ],
            'gfont' => [
                'resource' => 'https://fonts.googleapis.com',
                'type'     => 'preconnect'
            ],
            'twitter' => [
                'resource' => 'https://platform.twitter.com',
                'type'     => 'preconnect'
                ]
                
        ];

        foreach ($resourceHints as $resource) {
            $this->pageConfig->addRemotePageAsset(
                $resource['resource'],
                'link_rel',
                [
                    'attributes' => ['rel' => $resource['type'] ]
                ]
            );
        }

        return $this;
    }
}