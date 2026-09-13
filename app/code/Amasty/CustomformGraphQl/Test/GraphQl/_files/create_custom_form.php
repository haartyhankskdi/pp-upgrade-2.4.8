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

$formModel->setCode('amasty_test');
$formModel->setCreatedAt(date('Y-m-d 15:23:44'));
$formModel->setCustomerGroup('0,1,3');
$formModel->setEmailField('full_name');
$formModel->setEmailFieldHide(true);
$formModel->setFormJson("[[{\"type\":\"textinput\",\"name\":\"full_name\",\"label\":\"Amasty Full name\"}]]");
$formModel->setFormTitle("[\"Amasty Page Title\"]");
$formModel->setIsSurveyModeEnabled(true);
$formModel->setPopupButton('popup_test_button');
$formModel->setPopupShow(true);
$formModel->setSendNotification(true);
$formModel->setSendTo('amasty_test_email@amasty.com');
$formModel->setStatus(1);
$formModel->setStoreId(0);
$formModel->setSubmitButton('Amasty Submit');
$formModel->setSuccessMessage('Amasty Test GraphQl Success Message');
$formModel->setSuccessUrl('test-amasty.com');
$formModel->setTitle('Graph Ql Amasty Custom Form');

$formRepository->save($formModel);
