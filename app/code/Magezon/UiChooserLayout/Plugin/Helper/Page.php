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

namespace Magezon\UiChooserLayout\Plugin\Helper;

class Page
{
	/**
	 * @var \Magento\Cms\Model\Page
	 */
	protected $page;

	/**
	 * @param \Magento\Cms\Model\Page $page
	 */
	public function __construct(
		\Magento\Cms\Model\Page $page
	) {
		$this->page = $page;
	}

	public function afterPrepareResultPage(
		$subject,
		$result
	) {
		if ($result instanceof \Magento\Framework\View\Result\Page && $this->page->getId()) {
			$result->addHandle('cms_page_view_id_' . $this->page->getId());
		}
		return $result;
	}
}