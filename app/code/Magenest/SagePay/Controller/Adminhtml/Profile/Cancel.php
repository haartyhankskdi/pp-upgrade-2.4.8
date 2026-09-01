<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Profile;

use Magento\Framework\Controller\ResultFactory;
use Magenest\SagePay\Controller\Adminhtml\Profile;

class Cancel extends Profile
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $profile_id = $this->getRequest()->getParam('profile_id');
        try {
            /** @var \Magenest\SagePay\Model\Profile $profile */
            $profile = $this->_profileFactory->create()->load($profile_id);
            $profile->cancelSubscription();
            $this->messageManager->addSuccessMessage(__('You cancelled the profile.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __("Error"));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('sagepay/profile/index');
    }
}
