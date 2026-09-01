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

class Pages extends AbstractGroup
{
	public function getName()
	{
		return __('CMS Pages');
	}

	public function getChildren()
	{
		return [
			[
				'label' => __('All Pages'),
				'value' => 'all_pages'
			],
			[
				'label'  => __('Specific Pages'),
				'value'  => 'specific_pages',
				'url'    => $this->backendUrl->getUrl('uichooserlayout/chooser/pages')
			]
		];
	}

	public function isValid($conditions)
	{
		$handles = $this->getHandles();
		if (in_array('cms_page_view', $handles)) {
			foreach ($conditions as $row) {
				switch ($row['page_group']) {
					case 'all_pages':
						$valid = ($row['status'] == 'include' ? true : false);
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