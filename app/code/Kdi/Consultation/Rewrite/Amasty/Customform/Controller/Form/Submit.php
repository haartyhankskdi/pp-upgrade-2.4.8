<?php
namespace Kdi\Consultation\Rewrite\Amasty\Customform\Controller\Form;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Controller\Form\Submit as AmastySubmit;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Phrase;
use Magento\Framework\App\Action\Context;
use Amasty\Customform\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Amasty\Customform\Model\Submit as SubmitModel;
use Magento\Customer\Model\SessionFactory;
use Kdi\Consultation\Helper\CustomSession;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\Customform\Controller\Form\SessionData;

class Submit extends AmastySubmit
{
	protected $customSession;
	protected $product;
	protected $storeManager;


    public function __construct(
        SessionManagerInterface $session,
        Context $context,
        Data $helper,
        LoggerInterface $logger,
        SubmitModel $submit,
        SessionFactory $sessionFactory,
        CustomSession $customSession,
        ProductFactory $product,
        StoreManagerInterface $storeManager
    ) {
        // 🟢 Call parent constructor with full params
        parent::__construct(
            $session,
            $context,
            $helper,
            $logger,
            $submit,
            $sessionFactory
        );

        $this->logger = $logger;
        $this->customSession = $customSession;
        $this->product = $product;
        $this->storeManager = $storeManager;
        $this->submit = $submit;
         $this->sessionFactory = $sessionFactory;
         $this->helper = $helper;

        }

     public function execute()
     {
        

        $url = Data::REDIRECT_PREVIOUS_PAGE;
        $productId = $this->customSession->get('hh_product_id');

        $url = $this->submit->process($this->getRequest()->getParams());
                $this->_eventManager->dispatch(
                    'custom_checkbox_confirm_log',
                    ['customer' => $this->sessionFactory->create()->getCustomer()]
                );
                $type = self::SUCCESS_RESULT;
                $this->helper->setFormValue(1);
                $this->session->setData(SessionData::AM_CUSTOM_FORM_SESSION_DATA . $this->getFormId(), []);
        
        if ($this->getRequest()->isPost()) {
            try {

                $url = $this->submit->process($this->getRequest()->getParams());
                $this->_eventManager->dispatch(
                    'custom_checkbox_confirm_log',
                    ['customer' => $this->sessionFactory->create()->getCustomer()]
                );
                $type = self::SUCCESS_RESULT;
                $this->helper->setFormValue(1);
                $this->session->setData(SessionData::AM_CUSTOM_FORM_SESSION_DATA . $this->getFormId(), []);
            } catch (ValidatorException $e) {
                $this->processError($e, $this->getValidatorExceptionMessage());
            } catch (LocalizedException $e) {
                $this->processError($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->processError($e, $this->getExceptionMessage());
            }
        }

        if ($this->getRequest()->isAjax()) {
            $response = $this->getResponse()->representJson(
                $this->helper->encode(['result' => $type ?? self::ERROR_RESULT])
            );
        } else {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($url === Data::REDIRECT_PREVIOUS_PAGE) {
                $resultRedirect->setRefererUrl();
            } 
            $productUrl = $this->getProductUrl($productId). "?true";
            $storeId = $this->storeManager->getStore()->getId();
            

            if ($storeId==2){
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($url);
            }
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($productUrl);
        }

        return $response;
    }

    private function processError(\Exception $e, $message)
    {
        $this->logger->error($e->getMessage());
        $this->session->setData(
            \Amasty\Customform\Controller\Form\SessionData::AM_CUSTOM_FORM_SESSION_DATA . $this->getFormId(),
            $this->getRequest()->getParams()
        );
        $this->messageManager->addErrorMessage($message);
    }

    private function getValidatorExceptionMessage(): Phrase
    {
        return __('Server error occurred while saving form data. Please try again later or use Contact Us.');
    }

    private function getExceptionMessage(): Phrase
    {
        return __('Sorry. There is a problem with Your Form Request. Please try again or use Contact Us.');
    }

    private function getFormId(): int
    {
        return (int)$this->getRequest()->getParam(FormInterface::FORM_ID);
    }


    private function getProductUrl($productId){
    	$product = $this->product->create()->load($productId);
    	try {
        	return $product->getProductUrl();
	    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
	        return null;
	    }

    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
}
