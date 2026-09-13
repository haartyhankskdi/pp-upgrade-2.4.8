<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Adminhtml\Data\Edit;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Config\Source\Status;
use Amasty\Customform\ViewModel\Answser\AnswerIndex;
use Magento\Backend\Block\Template;

class Answer extends Template
{
    /**
     * Add buttons on request view page
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'back_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Back'),
                'onclick' => sprintf('setLocation("%s")', $this->getUrl('*/*/index')),
                'class' => 'back'
            ]
        );
        $this->getToolbar()->addChild(
            'delete_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Delete Data'),
                'onclick' => sprintf('setLocation("%s")', $this->getUrl('*/*/delete', [
                    'id' => $this->getCurrentResponse()->getAnswerId()
                ])),
                'class' => 'delete'
            ]
        );
        $this->getToolbar()->addChild(
            'export_pdf',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Download PDF'),
                'onclick' => sprintf('setLocation("%s")', $this->getUrl(
                    'amasty_customform/answer/exportAnswerToPdf',
                    [
                    'id' => $this->getCurrentResponse()->getAnswerId()
                    ]
                )),
                'class' => 'export_pdf'
            ]
        );
        $sendEmailButton = $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData(
                [
                    'label' => __('Send Email'),
                    'class' => 'action-save action-secondary',
                    'onclick' => 'document.querySelector(".am-send-email-form").submit()'
                ]
            );
        $this->setChild('submit_button', $sendEmailButton);

        parent::_prepareLayout();

        return $this;
    }

    public function getSubmitUrl(): string
    {
        return $this->getUrl('*/*/send', ['answer_id' => $this->getCurrentResponse()->getAnswerId()]);
    }

    public function getViewModel(): AnswerIndex
    {
        return $this->getData('view_model');
    }

    public function getCurrentResponse(): AnswerInterface
    {
        return $this->getViewModel()->getCurrentResponse();
    }

    public function isShowEmailSendingForm(): bool
    {
        return $this->getCurrentResponse()->getAdminResponseStatus() == Status::PENDING;
    }
}
