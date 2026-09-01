<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_UiChooserLayout
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\UiChooserLayout\Model\Group;

class PageLayout extends AbstractGroup
{
	public function getName()
	{
		return __('Page Layouts');
	}

	public function getChildren()
	{
		return [
			[
				'label' => __('Customer Dashboard'),
				'value' => 'customer_account_index'
			],
			[
				'label' => __('Checkout Page'),
				'value' => 'checkout_index_index'
			],
			[
				'label' => __('Cart Page'),
				'value' => 'checkout_cart_index'
			],
			[
				'label' => __('Home Page'),
				'value' => 'cms_index_index'
			]
		];
	}

	public function isValid($conditions)
	{
		$handles = $this->getHandles();
		foreach ($conditions as $row) {
			if (in_array($row['page_group'], $handles)) {
				$valid = ($row['status'] == 'include' ? true : false);
			}	
		}
		if (isset($valid)) return $valid;
	}
}