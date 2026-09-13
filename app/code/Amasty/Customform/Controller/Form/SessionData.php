<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Form;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form\Rendering\Autocomplete\ProcessorInterface;
use Amasty\Customform\Model\FormRepository;
use Amasty\Customform\Model\Utils\ProductRegistry;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;

class SessionData extends Action
{
    public const AM_CUSTOM_FORM_SESSION_DATA = 'am_custom_form_session_data';
    public const FORM_FIELDS = 'form_fields';
    public const PARAM_PRODUCT_ID = 'product_id';

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var FormRepository
     */
    private $formRepository;

    /**
     * @var ProcessorInterface
     */
    private $autocompleteProcessor;

    /**
     * @var ProductRegistry
     */
    private $productRegistry;

    public function __construct(
        Context $context,
        SessionManagerInterface $session,
        JsonFactory $resultJsonFactory,
        FormRepository $formRepository,
        ProcessorInterface $autocompleteProcessor,
        ProductRegistry $productRegistry
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->formRepository = $formRepository;
        $this->autocompleteProcessor = $autocompleteProcessor;
        $this->productRegistry = $productRegistry;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|void
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            return;
        }

        $result = $this->resultJsonFactory->create();
        $result->setData([self::FORM_FIELDS => $this->getPreselectData()]);

        return $result;
    }

    /**
     * @return array
     */
    private function getPreselectData()
    {
        $formId = $this->getFormId();
        $this->productRegistry->setProductId($this->getProductId());
        $preselectData = $this->session->getData(self::AM_CUSTOM_FORM_SESSION_DATA . $formId);
        if ($formId) {
            if (!$preselectData) {
                $preselectData = $this->getFormPreselectData($formId);
            }
        }

        return $preselectData;
    }

    /**
     * @param int $formId
     * @return array
     */
    private function getFormPreselectData(int $formId): array
    {
        $result['form_id'] = $formId;
        $form = $this->getForm($formId);
        $fieldValues = $form ? $this->autocompleteProcessor->process($form) : [];

        return array_merge($result, $fieldValues);
    }

    /**
     * @param int $id
     * @return \Amasty\Customform\Api\Data\FormInterface|null
     */
    private function getForm(int $id)
    {
        try {
            $form = $this->formRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $form = null;
        }

        return $form;
    }

    private function getFormId(): int
    {
        return (int) $this->getRequest()->getParam(FormInterface::FORM_ID);
    }

    private function getProductId(): int
    {
        return (int) $this->getRequest()->getParam(self::PARAM_PRODUCT_ID);
    }
}
