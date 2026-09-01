<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Amasty Custom Forms GraphQl for Magento 2 (System)
*/

declare(strict_types=1);

namespace Amasty\CustomformGraphQl\Model\Resolver\Query;

use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Helper\Data;
use Amasty\Customform\Model\SurveyAvailableResolver;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

class Form implements ResolverInterface
{
    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Amasty\Customform\Model\Form
     */
    private $surveyAvailableResolver;

    public function __construct(
        FormRepositoryInterface $formRepository,
        Data $helper,
        SurveyAvailableResolver $surveyAvailableResolver
    ) {
        $this->formRepository = $formRepository;
        $this->helper = $helper;
        $this->surveyAvailableResolver = $surveyAvailableResolver;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws \Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
            $form = $this->formRepository->get($args['formId']);
            $isSurveyAvailable = !$form->isSurveyModeEnabled()
                || $this->surveyAvailableResolver->isSurveyAvailable((int) $args['formId']);
            $form->setData('gdpr_enabled', $this->helper->isGDPREnabled($storeId));
            $form->setData('gdpr_text', $this->helper->getGDPRText($storeId));
            $form->setData('advanced_google_key', $this->helper->getGoogleKey());
            $form->setData('advanced_date_format', $this->helper->getDateFormat());
            $form->setData('isSurvey', $form->isSurveyModeEnabled());
            $form->setData('is_form_available', $isSurveyAvailable);
        } catch (\Exception $e) {
            return ['error' => 'Wrong parameters.'];
        }

        return $form;
    }
}
