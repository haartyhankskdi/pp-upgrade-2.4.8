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

class PageGroupProvider
{
    /**
     * @var array
     */
    protected $groups;

    /**
     * @param array $groups
     */
	public function __construct(
        array $groups = []
    ) {
        $this->groups = $groups;
    }

    public function getGroups()
    {
        foreach ($this->groups as $k => &$group) {
            $group->setKey($k);
        }
        return $this->groups;
    }

    public function getGroup($type)
    {
        foreach ($this->getGroups() as $k => $group) {
            $children = $group->getChildren();
            if ($children) {
                foreach ($children as $key => $item) {
                    if ($item['value'] == $type) {
                        return $group;
                    }
                }
            }
        }

        foreach ($this->getGroups() as $k => $group) {
            if ($k == $type) {
                return $group;
            }
        }
    }

    public function getOptions()
    {
    	$options = [];
    	foreach ($this->getGroups() as $k => $group) {
            $children = $group->getChildren();
    		$option = [
                'label' => $group->getName(),
                'value' => $k
    		];
            if ($children) {
                $option['optgroup'] = $children;
            }
            $options[] = $option;
    	}
    	return $options;
    }
}