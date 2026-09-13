<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Amasty Custom Forms GraphQl for Magento 2 (System)
 */

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var FormInterface $formModel */
$formModel = $objectManager->create(FormInterface::class);

/** @var FormRepositoryInterface $formRepository **/
$formRepository = $objectManager->create(FormRepositoryInterface::class);

$formJsonArray = [
    [
        [
            'type' => 'textinput',
            'name' => 'textinput-amasty',
            'label' => 'Amasty Text Input',
            'className' => 'form-control',
            'parentType' => 'input'
        ],
        [
            'type' => 'number',
            'name' => 'number-amasty',
            'label' => 'Amasty Number Input',
            'className' => 'form-control',
            'parentType' => 'input'
        ],
        [
            'type' => 'date',
            'name' => 'date-amasty',
            'label' => 'Amasty Date',
            'className' => 'amform-date',
            'parentType' => 'select',
            'format' => 'mm/dd/yyyy'
        ],
        [
            'type' => 'dropdown',
            'name' => 'dropdown-amasty',
            'label' => 'Amasty DropDown',
            'className' => 'form-control',
            'parentType' => 'options',
            'values' => [
                [
                    'label' => 'Amasty Option 1',
                    'value' => 'am-option-1',
                    'selected' => '1'
                ],
                [
                    'label' => 'Amasty Option 2',
                    'value' => 'am-option-2',
                ]
            ]
        ],
        [
            'type' => 'checkbox',
            'name' => 'checkbox-amasty',
            'label' => 'Amasty Checkbox',
            'className' => 'amform-checkbox',
            'parentType' => 'options',
            'values' => [
                [
                    'label' => 'Amasty Checkbox 1',
                    'value' => 'am-checkbox-1'
                ],
                [
                    'label' => 'Amasty Checkbox 2',
                    'value' => 'am-checkbox-2',
                    'selected' => '1'
                ]
            ]
        ],
        [
            'type' => 'radio',
            'name' => 'radio-amasty',
            'label' => 'Amasty Radio',
            'className' => 'amform-radio',
            'parentType' => 'options',
            'values' => [
                [
                    'label' => 'Amasty Radio 1',
                    'value' => 'am-radio-1',
                    'selected' => '1'
                ],
                [
                    'label' => 'Amasty Radio 2',
                    'value' => 'am-radio-2'
                ]
            ]
        ],
        [
            'type' => 'rating',
            'name' => 'rating-amasty',
            'label' => 'Amasty Rating',
            'className' => 'amform-rating',
            'parentType' => 'other',
            'values' => [
                [
                    'label' => 'Am Star 1',
                    'value' => 'am-star-1'
                ],
                [
                    'label' => 'Am Star 2',
                    'value' => 'am-star-2'
                ],
                [
                    'label' => 'Am Star 3',
                    'value' => 'am-star-3'
                ],
                [
                    'label' => 'Am Star 4',
                    'value' => 'am-star-4',
                    'selected' => '1'
                ],
                [
                    'label' => 'Am Star 5',
                    'value' => 'am-star-5'
                ]
            ]
        ]
    ]
];

$formModel->setCode('amasty_big_form_test');
$formModel->setStatus(1);
$formModel->setStoreId(0);
$formModel->setFormJson(json_encode($formJsonArray));
$formModel->setFormTitle("[\"Amasty Page Title\"]");
$formModel->setTitle('Graph Ql Amasty Big Custom Form');

$formRepository->save($formModel);
