<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Card;

use Magento\Backend\App\Action;

class Delete extends \Magento\Backend\App\Action
{
    protected $_cardFactory;

    public function __construct(
        Action\Context $context,
        \Magenest\SagePay\Model\CardFactory $cardFactory
    ) {
        $this->_cardFactory = $cardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                /** @var \Magenest\SagePay\Model\Card $card */
                $card = $this->_cardFactory->create()->load($id);
                $card->delete();
                $this->messageManager->addSuccessMessage(__("Deleted"));

                return $this->_redirect('sagepay/card/index');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __("Error"));
            }
        }
        $this->messageManager->addErrorMessage(__("Can't specify card"));

        return $this->_redirect('sagepay/card/index');
    }
}
