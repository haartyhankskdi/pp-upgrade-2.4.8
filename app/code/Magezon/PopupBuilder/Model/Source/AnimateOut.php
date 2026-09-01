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
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model\Source;

class AnimateOut
{
    /**
     * @return array
     */
    public function getOptions()
    {
        $groups = [];
        $groups[] = [
            'label'    => 'Bouncing Exits',
            'children' => [
                'bounceOut',
                'bounceOutDown',
                'bounceOutLeft',
                'bounceOutRight',
                'bounceOutUp'
            ]
        ];

        $groups[] = [
            'label'    => 'Fading Exits',
            'children' => [
                'fadeOut',
                'fadeOutDown',
                'fadeOutDownBig',
                'fadeOutLeft',
                'fadeOutLeftBig',
                'fadeOutRight',
                'fadeOutRightBig',
                'fadeOutUp',
                'fadeOutUpBig'
            ]
        ];

        $groups[] = [
            'label'    => 'Flippers',
            'children' => [
                'flipOutX',
                'flipOutY'
            ]
        ];

        $groups[] = [
            'label'    => 'Lightspeed',
            'children' => [
                'lightSpeedOut'
            ]
        ];

        $groups[] = [
            'label'    => 'Rotating Exits',
            'children' => [
                'rotateOut',
                'rotateOutDownLeft',
                'rotateOutDownRight',
                'rotateOutUpLeft',
                'rotateOutUpRight'
            ]
        ];

        $groups[] = [
            'label'    => 'Specials',
            'children' => [
                'hinge',
                'rollOut'
            ]
        ];

        $groups[] = [
            'label'    => 'Zoom Exits',
            'children' => [
                'zoomOut',
                'zoomOutDown',
                'zoomOutLeft',
                'zoomOutRight',
                'zoomOutUp'
            ]
        ];

        $groups[] = [
            'label'    => 'Slide Exits',
            'children' => [
                'slideOutDown',
                'slideOutLeft',
                'slideOutRight',
                'slideOutUp'
            ]
        ];

        foreach ($groups as $k => $group) {
            $option = [
                'label' => $group['label'],
                'value' => $k
            ];
            $children = [];
            foreach ($group['children'] as $k1 => $item) {
                $children[] = [
                    'label' => $item,
                    'value' => $item
                ];
            }
            $option['optgroup'] = $children;
            $options[] = $option;
        }
        return $options;
    }
}
