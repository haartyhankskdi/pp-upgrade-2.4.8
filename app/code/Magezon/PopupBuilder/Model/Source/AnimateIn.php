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

class AnimateIn
{
    /**
     * @return array
     */
    public function getOptions()
    {
        $groups = [];
        $groups[] = [
            'label'    => 'Attention Seekers',
            'children' => [
                'bounce',
                'flash',
                'pulse',
                'rubberBand',
                'shake',
                'swing',
                'tada',
                'wobble',
                'jello',
            ]
        ];

        $groups[] = [
            'label'    => 'Bouncing Entrances',
            'children' => [
                'bounceIn',
                'bounceInDown',
                'bounceInLeft',
                'bounceInRight',
                'bounceInUp'
            ]
        ];

        $groups[] = [
            'label'    => 'Fading Entrances',
            'children' => [
                'fadeIn',
                'fadeInDown',
                'fadeInDownBig',
                'fadeInLeft',
                'fadeInLeftBig',
                'fadeInRight',
                'fadeInRightBig',
                'fadeInUp',
                'fadeInUpBig',
            ]
        ];

        $groups[] = [
            'label'    => 'Flippers',
            'children' => [
                'flipInX',
                'flipInY'
            ]
        ];

        $groups[] = [
            'label'    => 'Lightspeed',
            'children' => [
                'lightSpeedIn'
            ]
        ];

        $groups[] = [
            'label'    => 'Rotating Entrances',
            'children' => [
                'rotateIn',
                'rotateInDownLeft',
                'rotateInDownRight',
                'rotateInUpLeft',
                'rotateInUpRight'
            ]
        ];

        $groups[] = [
            'label'    => 'Rotating Entrances',
            'children' => [
                'rotateIn',
                'rotateInDownLeft',
                'rotateInDownRight',
                'rotateInUpLeft',
                'rotateInUpRight'
            ]
        ];

        $groups[] = [
            'label'    => 'Specials',
            'children' => [
                'rollIn'
            ]
        ];

        $groups[] = [
            'label'    => 'Zoom Entrances',
            'children' => [
                'zoomIn',
                'zoomInDown',
                'zoomInLeft',
                'zoomInRight',
                'zoomInUp'
            ]
        ];

        $groups[] = [
            'label'    => 'Slide Entrances',
            'children' => [
                'slideInDown',
                'slideInLeft',
                'slideInRight',
                'slideInUp',
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

        $options[] = [
            'label'    => __('Custom'),
            'value'    => 'custom',
            'optgroup' => [
                [
                    'label' => 'Top to Bottom',
                    'value' => 'mgz_top-to-bottom'
                ],
                [
                    'label' => 'Bottom to Top',
                    'value' => 'mgz_bottom-to-top'
                ],
                [
                    'label' => 'Left to Right',
                    'value' => 'mgz_left-to-right'
                ],
                [
                    'label' => 'Right to left',
                    'value' => 'mgz_right-to-left'
                ],
                [
                    'label' => 'Bottom to Top',
                    'value' => 'mgz_bottom-to-top'
                ],
                [
                    'label' => 'Appear from center',
                    'value' => 'mgz_appear'
                ]
            ]
        ];

        return $options;
    }
}
