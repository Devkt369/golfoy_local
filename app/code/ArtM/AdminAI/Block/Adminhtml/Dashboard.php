<?php
namespace ArtM\AdminAI\Block\Adminhtml;

use Magento\Backend\Block\Template;

class Dashboard extends Template
{
	private const ALLOWED_SECTIONS = [
		'overview',
		'sales',
		'products',
		'customers',
		'notifications',
	];

	public function getCurrentSection(): string
	{
		$section = (string)$this->getRequest()->getParam('section', 'overview');

		return in_array($section, self::ALLOWED_SECTIONS, true) ? $section : 'overview';
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function getSidebarItems(): array
	{
		$urlParams = $this->getStoreAwareUrlParams();

		return [
			[
				'code' => 'overview',
				'label' => 'Overview',
				'url' => $this->getUrl('adminai/dashboard/index', array_merge($urlParams, ['section' => 'overview'])),
			],
			[
				'code' => 'sales',
				'label' => 'Sales Analytics',
				'url' => $this->getUrl('adminai/dashboard/index', array_merge($urlParams, ['section' => 'sales'])),
			],
			[
				'code' => 'products',
				'label' => 'Product Analytics',
				'url' => $this->getUrl('adminai/dashboard/index', array_merge($urlParams, ['section' => 'products'])),
			],
			[
				'code' => 'customers',
				'label' => 'Customer Analytics',
				'url' => $this->getUrl('adminai/dashboard/index', array_merge($urlParams, ['section' => 'customers'])),
			],
			[
				'code' => 'notifications',
				'label' => 'Notifications',
				'url' => $this->getUrl('adminai/dashboard/index', array_merge($urlParams, ['section' => 'notifications'])),
			],
		];
	}

	public function isSectionActive(string $section): bool
	{
		return $this->getCurrentSection() === $section;
	}

	public function getSectionTitle(): string
	{
		$titles = [
			'overview' => 'Overview',
			'sales' => 'Sales Analytics',
			'products' => 'Product Analytics',
			'customers' => 'Customer Analytics',
			'notifications' => 'Notifications',
		];

		return $titles[$this->getCurrentSection()] ?? 'Overview';
	}

	/**
	 * @return array<string,string>
	 */
	public function getSectionDataUrls(): array
	{
		$urlParams = $this->getStoreAwareUrlParams();

		return [
			'overview' => $this->getUrl('adminai/dashboard_data/overview', $urlParams),
			'sales' => $this->getUrl('adminai/dashboard_data/sales', $urlParams),
			'products' => $this->getUrl('adminai/dashboard_data/product', $urlParams),
			'customers' => $this->getUrl('adminai/dashboard_data/customer', $urlParams),
			'notifications' => $this->getUrl('adminai/dashboard_data/notification', $urlParams),
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function getStoreAwareUrlParams(): array
	{
		$store = trim((string)$this->getRequest()->getParam('store', ''));
		if ($store === '') {
			return [];
		}

		return ['store' => $store];
	}
}