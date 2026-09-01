<?php

namespace Kdi\Popup\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Kdi\Popup\Model\EmailFactory;
use Kdi\Popup\Model\ResourceModel\Email as EmailResource;

/**
 * Saves an email submitted from the homepage product launch popup.
 */
class Subscribe extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $resultJsonFactory;
    private EmailFactory $emailFactory;
    private EmailResource $emailResource;
    private RemoteAddress $remoteAddress;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EmailFactory $emailFactory,
        EmailResource $emailResource,
        RemoteAddress $remoteAddress
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->emailFactory = $emailFactory;
        $this->emailResource = $emailResource;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * This is a public, anonymous, AJAX-only endpoint, so standard
     * form-key CSRF validation is bypassed here.
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $email = trim((string)$this->getRequest()->getParam('email'));
        $productId = (int)$this->getRequest()->getParam('product_id');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'success' => false,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        try {
            $model = $this->emailFactory->create();
            $model->setEmail($email);
            $model->setProductId($productId ?: null);
            $model->setCustomerIp($this->remoteAddress->getRemoteAddress());
            $this->emailResource->save($model);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Something went wrong. Please try again.')
            ]);
        }

        return $result->setData([
            'success' => true,
            'message' => __('Thank you! We will notify you when it launches.')
        ]);
    }
}
