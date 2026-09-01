<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Controller\Adminhtml\Forms;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form;
use Amasty\Customform\Model\Form\Save\Preparation\PreparationInterface;
use Amasty\Customform\Model\FormFactory;
use Amasty\Customform\Model\FormRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Model\Layout\Update\ValidatorFactory;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    /**
     * @var ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormRepository
     */
    private $formRepository;

    /**
     * @var PreparationInterface
     */
    private $formDataPreparationProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ActionContext $context,
        ValidatorFactory $validatorFactory,
        FormFactory $formFactory,
        FormRepository $formRepository,
        PreparationInterface $formDataPreparationProcessor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->validatorFactory = $validatorFactory;
        $this->formFactory = $formFactory;
        $this->formRepository = $formRepository;
        $this->formDataPreparationProcessor = $formDataPreparationProcessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Customform::page');
    }

    public function validate($data)
    {
        $errorNo = true;
        if (!empty($data['layout_update_xml'])) {
            /** @var $validatorCustomLayout \Magento\Framework\View\Model\Layout\Update\Validator */
            $validatorCustomLayout = $this->validatorFactory->create();
            if (!empty($data['layout_update_xml']) && !$validatorCustomLayout->isValid($data['layout_update_xml'])) {
                $errorNo = false;
            }
            foreach ($validatorCustomLayout->getMessages() as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        }
        return $errorNo;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $id = $data[FormInterface::FORM_ID] ?? 0;

        if ($data) {
            /** @var Form $model */
            $model = $this->formFactory->create();

            try {
                if ($id) {
                    $model = $this->formRepository->get($id);
                }

                $data = $this->formDataPreparationProcessor->prepare($data);
                $model->setData($data);
                $session = $this->_getSession();
                $session->setAmCustomFormData($data);
                $this->formRepository->save($model);
                $session->unsAmCustomFormData();
                $this->messageManager->addSuccessMessage(__('You have saved this form.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['form_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException | \RuntimeException $e) {
                $this->logger->critical($e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the page.'));
            }

            return $resultRedirect->setPath('*/*/edit', ['form_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
