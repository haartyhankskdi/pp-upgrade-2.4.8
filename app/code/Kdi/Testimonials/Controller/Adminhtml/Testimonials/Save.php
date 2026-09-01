<?php
declare(strict_types=1);

namespace Kdi\Testimonials\Controller\Adminhtml\Testimonials;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;
use Kdi\Testimonials\Model\TestimonialsFactory;
use Magento\Framework\App\Filesystem\DirectoryList as DList;
/**
 * Class Save
 *
 * Save Testimonials entity from Admin Panel
 */
class Save extends Action
{
    /** Admin ACL */
    const ADMIN_RESOURCE = 'Kdi_Testimonials::kdi_testimonials_testimonials';

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var TestimonialsFactory */
    private $testimonialsFactory;

    /** @var UploaderFactory */
    private $uploaderFactory;

    /** @var DirectoryList */
    private $directoryList;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Save constructor.
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        TestimonialsFactory $testimonialsFactory,
        UploaderFactory $uploaderFactory,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->dataPersistor      = $dataPersistor;
        $this->testimonialsFactory = $testimonialsFactory;
        $this->uploaderFactory    = $uploaderFactory;
        $this->directoryList      = $directoryList;
        $this->logger             = $logger;
    }

    /**
     * Execute Save Action
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $postData = (array) $this->getRequest()->getPostValue();

        if (!$postData) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $id    = (int)$this->getRequest()->getParam('testimonials_id');
            $model = $this->testimonialsFactory->create();

            if ($id) {
                $model->load($id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This testimonial no longer exists.'));
                }
            }

            /** Upload Images */
            $images = $this->uploadImages(['image1', 'image2']);

            /** Set Data */
            $model->addData([
                'product_id'  => $postData['product_id'] ?? null,
                'title'       => $postData['title'] ?? '',
                'review'      => $postData['review'] ?? '',
                'meta_title'  => $postData['meta_title'] ?? '',
                'meta_desc'   => $postData['meta_desc'] ?? '',
                'meta_keyword'=> $postData['meta_keyword'] ?? '',
                'meta_url'    => $postData['url'] ?? '',
                'status'      => $postData['status'] ?? 1,
                'robots'      => $postData['robots'] ?? 1,
                'message'     => $postData['message'] ?? '',
                'review_listing'     => $postData['review_listing'] ?? '',

            ]);

            /** Update images only if uploaded */
            if (!empty($images['image1'])) {
                $model->setImage1($images['image1']);
            }
            if (!empty($images['image2'])) {
                $model->setImage2($images['image2']);
            }

            $model->save();

            $this->messageManager->addSuccessMessage(__('Testimonial saved successfully.'));
            $this->dataPersistor->clear('kdi_testimonials');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['testimonials_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/');

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('Something went wrong while saving testimonial.'));
        }

        $this->dataPersistor->set('kdi_testimonials', $postData);
        return $resultRedirect->setPath('*/*/edit', [
            'testimonials_id' => $this->getRequest()->getParam('testimonials_id')
        ]);
    }

    /**
     * Upload Images Securely
     *
     * @param array $fields
     * @return array
     */
    private function uploadImages(array $fields): array
    {
        $uploaded = [];
        $mediaPath = $this->directoryList->getPath(DList::MEDIA) . '/testimonials';

        foreach ($fields as $field) {
            try {
                if (!empty($_FILES[$field]['name'])) {

                    $uploader = $this->uploaderFactory->create(['fileId' => $field]);

                    $uploader->setAllowedExtensions(['jpg','jpeg','png','gif','webp']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->skipDbProcessing(true);

                    $result = $uploader->save($mediaPath);

                    if (!empty($result['file'])) {
                        $uploaded[$field] = 'testimonials' . $result['file'];
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning("Image upload failed for {$field}: " . $e->getMessage());
            }
        }

        return $uploaded;
    }
}
