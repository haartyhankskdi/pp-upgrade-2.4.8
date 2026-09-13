<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

class Delete extends \Amasty\Customform\Controller\Adminhtml\Answer
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->answerRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('Answer was deleted.'));

                return $this->_redirect('amasty_customform/answer/index');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete item right now. Please review the log and try again.')
                );
                $this->logger->critical($e);

                return $this->_redirect('amasty_customform/*/edit', ['id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('Can\'t find a item to delete.'));

        return $this->_redirect('amasty_customform/answer/index');
    }
}
