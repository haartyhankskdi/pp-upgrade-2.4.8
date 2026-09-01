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

class Products extends AbstractGroup
{
	public function getName()
	{
		return __('Products');
	}

	public function getChildren()
	{
		return [
			[
				'label'  => __('All Product Types'),
				'value'  => 'all_products',
				'url'    => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Simple Product'),
				'value' => 'simple_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Virtual Product'),
				'value' => 'virtual_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Bundle Product'),
				'value' => 'bundle_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Downloadable Product'),
				'value' => 'downloadable_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Configurable Product'),
				'value' => 'configurable_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			],
			[
				'label' => __('Grouped Product'),
				'value' => 'grouped_products',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/products')
			]
		];
	}

	public function isValid($conditions)
	{
		$handles = $this->getHandles();
		if (in_array('catalog_product_view', $handles)) {
			foreach ($conditions as $row) {
				switch ($row['page_group']) {
					case 'all_products':
						$valid = ($row['status'] == 'include' ? true : false);
						break;

					case 'simple_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_simple', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;

					case 'virtual_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_virtual', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;

					case 'bundle_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_bundle', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;

					case 'downloadable_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_downloadable', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;

					case 'configurable_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_configurable', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;

					case 'grouped_products':
						if ($row['page_group_type'] == 'all') {
							if (in_array('catalog_product_view_type_grouped', $handles)) {
								$valid = ($row['status'] == 'include' ? true : false);
							}	
						} else {
							if (isset($row['entities'])) {
								$entities = explode(',', $row['entities']);
								foreach ($entities as $id) {
									if ($id && in_array('catalog_product_view_id_' . $id, $handles)) {
										$valid = ($row['status'] == 'include' ? true : false);
									}
								}
							}
						}
						break;
				}
			}
		}
		if (isset($valid)) return $valid;
	}
}