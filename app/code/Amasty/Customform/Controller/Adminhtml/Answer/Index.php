<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Controller\Adminhtml\Answer;
use Magento\Backend\Model\View\Result\Page;

class Index extends Answer
{
    public function execute(): Page
    {
        if ($formId = (int)$this->getRequest()->getParam('form_id', null)) {
            $this->bookmark->applyFilter(
                'amasty_customform_answer_listing',
                [
                    'form_id' => $formId,
                    AnswerInterface::ADMIN_RESPONSE_STATUS => $this->getRequest()->getParam('status', null)
                ]
            );
            $this->bookmark->clear();
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Submitted Data'));
        $resultPage->addBreadcrumb(__('Amasty: Custom Forms'), __('Submitted Data'));

        return $resultPage;
    }
}
