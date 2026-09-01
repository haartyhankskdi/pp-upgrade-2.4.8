<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Form;

use Magenest\SagePay\Helper\SagepayAPI;
use Magenest\SagepayLib\Classes\Constants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\QuoteValidator;
use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magenest\SagepayLib\Classes\SagepaySettings;
use tests\verification\Tests\HookActionsTest;

class Build extends Action
{
    protected $coreRegistry;

    protected $_formKeyValidator;

    protected $checkoutSession;

    protected $sageHelper;

    protected $sageLogger;

    protected $quoteValidator;

    protected $customerManagement;

    protected $dataHelper;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    private $decoder;

    /**
     * Build constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param CustomerManagement $customerManagement
     * @param QuoteValidator $quoteValidator
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Url\DecoderInterface $decoder
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        CustomerManagement $customerManagement,
        QuoteValidator $quoteValidator,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Url\DecoderInterface $decoder
    ) {
        $this->coreRegistry = $registry;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->quoteValidator = $quoteValidator;
        $this->customerManagement = $customerManagement;
        $this->dataHelper = $dataHelper;
        $this->escaper = $escaper;
        $this->decoder = $decoder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                $e = new SagepayApiException("Invalid Form Key");
                $this->dataHelper->debugException($e);
                $result->setData([
                    'error' => true,
                    'message' => $e->getMessage()
                ]);
                return $result;
            }
            if ($this->getRequest()->isAjax()) {
                $quote = $this->checkoutSession->getQuote();
                $this->quoteValidator->validateBeforeSubmit($quote);
                if (!$quote->getCustomerIsGuest()) {
                    if ($quote->getCustomerId()) {
                        if (method_exists($this->customerManagement, 'validateAddresses')) {
                            $this->customerManagement->validateAddresses($quote);
                        }
                    }
                }
                $guestEmail = $this->getRequest()->getParam('guest_email');
                $config = [
                    'currency' => $this->dataHelper->getCurrency($quote),
                    'txType' => $this->sageHelper->getSageFormPaymentAction()
                ];
                $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
                $sageConfig = SagepaySettings::getInstance($apiConfig, false);
                $api = $this->sageHelper->handleSageApi($quote, $guestEmail, $sageConfig);
                if ($api) {
                    $request = $api->createRequest();
                    $quote = $this->sageHelper->handleQuoteDetailInformation($api, $quote);
                    $quote->save();
                    $queryString = $this->escaper->escapeHtml(
                        $this->decoder->decode(utf8_encode($api->getQueryData()))
                    );
                    $this->sageLogger->debug("Begin SagePay Form");
                    $this->sageLogger->debug($queryString);
                    $result->setData([
                        'success' => true,
                        'request' => $request,
                        'purchaseUrl' => $sageConfig->getPurchaseUrl(Constants::SAGEPAY_FORM, $sageConfig->getEnv()),
                    ]);
                } else {
                    $result->setData([
                        'error' => true,
                        'message' => __("Payment Request Error")
                    ]);
                }
            } else {
                $result->setData([
                    'error' => true,
                    'message' => __("Invalid request")
                ]);
            }
        } catch (\Magento\Framework\Validator\Exception $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (LocalizedException $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => __("Payment error")
            ]);
        } finally {
            return $result;
        }
    }
}
