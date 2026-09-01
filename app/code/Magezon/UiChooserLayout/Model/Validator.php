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

namespace Magezon\UiChooserLayout\Model;

class Validator
{
    /**
     * @var \Magezon\UiChooserLayout\Model\PageGroupProvider
     */
    protected $pageGroupProvider;

    /**
     * @param \Magezon\UiChooserLayout\Model\PageGroupProvider $pageGroupProvider
     */
	public function __construct(
        \Magezon\UiChooserLayout\Model\PageGroupProvider $pageGroupProvider
    ) {
        $this->pageGroupProvider = $pageGroupProvider;
    }

    public function isValid($conditions)
    {
        $valid = false;
        $_conditions = [];
        foreach ($conditions as $_condition) {
            $group = $this->pageGroupProvider->getGroup($_condition['page_group']);
            if ($group) {
                $_conditions[$group->getKey()][] = $_condition;
            }
        }

        $groups = $this->pageGroupProvider->getGroups();
        foreach ($groups as $group) {
            if (isset($_conditions[$group->getKey()])) {
                $tmp = $group->isValid($_conditions[$group->getKey()]);
                if (is_bool($tmp)) $valid = $tmp;
            }
        }

        return $valid;
    }
}