<?php
namespace Kdi\ImageUpload\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Customer\Model\Session;
use Kdi\ImageUpload\Model\EntityFactory;
use Magento\Framework\App\Filesystem\DirectoryList as DList;
use Magento\Framework\Message\ManagerInterface;
use Kdi\ImageUpload\Helper\CustomCookie;

class Upload extends Action
{
    protected $resultJsonFactory;
    protected $uploaderFactory;
    protected $directoryList;
    protected $customerSession;
    protected $entityFactory;
    protected $logger;
    protected $resultFactory;
    protected $messageManager;
     protected $customCookie;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        UploaderFactory $uploaderFactory,
        DirectoryList $directoryList,
        Session $customerSession,
        EntityFactory $entityFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        ManagerInterface $messageManager,
          CustomCookie $customCookie
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->directoryList = $directoryList;
        $this->customerSession = $customerSession;
        $this->entityFactory = $entityFactory;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
         $this->customCookie = $customCookie;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $customerId = $this->customerSession->getCustomerId();

        $params = $this->getRequest()->getParams();
       $orderId =  $this->customCookie->get();    

       $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/image.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" ========================================== " );
        $zendLogger->info(print_r($params, true));

        if (!$customerId) {
            return $resultJson->setData(['success' => false, 'message' => __('Customer is not logged in.')]);
        }

        if (empty($params['order_id'])) {
            $this->messageManager->addErrorMessage(__("Order ID does not exist. Please use the URL provided in the email."));
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
                ->setUrl('/');
        }


        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/image.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" ========================================== " );
        $zendLogger->info(" orderId" . $orderId);

        $zendLogger->info(print_r($params, true));

        $uploadedFiles = $this->getRequest()->getFiles()->toArray();
        $uploadedImages = [];

        try {
            foreach ($uploadedFiles as $key => $file) {
                if (isset($file['name']) && $file['name']) {
                    $imagePath = $this->uploadImage($key);

                       $zendLogger->info(" key " . $key );
                       $zendLogger->info(" image path " . $imagePath );


                    if ($imagePath) {

                        $uploadedImages[$key] = $imagePath;
                    }
                }
            }

            $zendLogger->info(" ======================= " );
            $zendLogger->info(print_r($uploadedImages , true));



            // Save image details to the database
            if (!empty($uploadedImages)) {
                $model = $this->entityFactory->create();
                if ($params['entity_id']) {
                    $model = $model->load($params['entity_id']);
                }

                if ($params['order_id']) {
                    $model = $model->load($orderId, 'order_id');
                }

                $model->setFullImage($uploadedImages['image1'] ?? null); // Adjust key if necessary
                $model->setFullImage2($uploadedImages['image2'] ?? null);
                $model->setIdentityImage($uploadedImages['image3'] ?? null); // Adjust key if necessary
                $model->setCustomerId($customerId);
                $model->setOrderId($params['order_id']);
                $this->customCookie->delete();    
               $res =  $model->save();
               if ($res) {
                    $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                    $redirect->setUrl('/image-upload-success');
                    return $redirect;
               }


                return $resultJson->setData(['success' => true, 'message' => __('Images uploaded successfully.'), 'images' => $uploadedImages]);
            }
            $this->messageManager->addWarning(__("No valid images uploaded."));
            return $resultJson->setData(['success' => false, 'message' => __('No valid images uploaded.')]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
              $this->messageManager->addError(__('An error occurred during upload.'));
            return $resultJson->setData(['success' => false, 'message' => __('An error occurred during upload.')]);
        }
    }

    private function uploadImage($fileId)
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);

            $mediaDirectory = $this->directoryList->getPath(DList::MEDIA);
            $result = $uploader->save($mediaDirectory . '/frontend_uploads');

            return 'media/frontend_uploads' . $result['file'];
        } catch (\Exception $e) {
            $this->logger->error(__('File upload failed: ') . $e->getMessage());
            return null;
        }
    }
}
