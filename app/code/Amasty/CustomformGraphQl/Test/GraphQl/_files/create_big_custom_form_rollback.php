<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Amasty Custom Forms GraphQl for Magento 2 (System)
 */

use Amasty\Customform\Api\FormRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var FormRepositoryInterface $formRepository **/
$formRepository = $objectManager->create(FormRepositoryInterface::class);

$formEntity = $formRepository->getByFormCode('amasty_big_form_test');
if ($formEntity) {
    $form = $formRepository->delete($formEntity);
}
