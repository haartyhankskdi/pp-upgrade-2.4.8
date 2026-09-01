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

class Categories extends AbstractGroup
{
	public function getName()
	{
		return __('Categories');
	}

	public function getChildren()
	{
		return [
			[
				'label' => __('Anchor Categories'),
				'value' => 'anchor_categories',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/categories')
			],
			[
				'label' => __('Non-Anchor Categories'),
				'value' => 'notanchor_categories',
				'url'   => $this->backendUrl->getUrl('uichooserlayout/chooser/categories')
			]
		];
	}

	public function isValid($conditions)
	{
		$handles = $this->getHandles();
		if (in_array('catalog_category_view', $handles)) {
			foreach ($conditions as $row) {
				switch ($row['page_group']) {

					case 'anchor_categories':
						if (in_array('catalog_category_view_type_layered', $handles)) {
							if ($row['page_group_type'] == 'all') {
								$valid = ($row['status'] == 'include' ? true : false);
							} else {
								if (isset($row['entities'])) {
									$entities = explode(',', $row['entities']);
									foreach ($entities as $id) {
										if ($id && in_array('catalog_category_view_id_' . $id, $handles)) {
											$valid = ($row['status'] == 'include' ? true : false);
										}
									}
								}
							}
						}
						break;

					case 'notanchor_categories':
						if (in_array('catalog_category_view_type_default', $handles)) {
							if ($row['page_group_type'] == 'all') {
								$valid = ($row['status'] == 'include' ? true : false);
							} else {
								if (isset($row['entities'])) {
									$entities = explode(',', $row['entities']);
									foreach ($entities as $id) {
										if ($id && in_array('catalog_category_view_id_' . $id, $handles)) {
											$valid = ($row['status'] == 'include' ? true : false);
										}
									}
								}
							}
						}
						break;

					case 'specific_pages':
						if (isset($row['entities'])) {
							$entities = explode(',', $row['entities']);
							foreach ($entities as $id) {
								if ($id && in_array('cms_page_view_id_' . $id, $handles)) {
									$valid = ($row['status'] == 'include' ? true : false);
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