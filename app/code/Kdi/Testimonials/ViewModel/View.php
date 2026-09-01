<?php
declare(strict_types=1);

namespace Kdi\Testimonials\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Kdi\Testimonials\Model\TestimonialsFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;



/**
 * Testimonial ViewModel
 */
class View implements ArgumentInterface
{
    protected $testimonialsFactory;
    protected $request;
    protected $registry;

    /**
     * Cached loaded model instance (or null if not loaded)
     *
     * @var DataObject|null
     */
    private ?DataObject $model = null;

    public function __construct(
        TestimonialsFactory $testimonialsFactory,
        RequestInterface $request,
        \Magento\Framework\Registry $registry
    ) {
        $this->testimonialsFactory = $testimonialsFactory;
        $this->request = $request;
        $this->registry = $registry;
        
    }

    /**
     * Simple example method
     */
    public function getSomething(): string
    {
        return 'Hello World';
    }

    /**
     * Load and return the model instance (cached).
     */
    public function getModelData(): ?DataObject
    {
        // Return cached instance if already loaded
        if ($this->model !== null) {
            return $this->model;
        }

        $id = (int) $this->request->getParam('id');
        $data = $this->registry->registry('success_story');
       // print_r($data->getData());
        
        // Return the data from the registry if available
        $this->model = $data instanceof DataObject ? $data : null;
        return $this->model;
    }

    /**
     * Return before image path or null
     */
    public function getBeforeImage(): ?string
    {

        $data = $this->getModelData();
        return $data ? (string) $data->getData('image1') : null;
    }

    /**
     * Return after image path or null
     */
    public function getAfterImage(): ?string
    {
        $data = $this->getModelData();
        return $data ? (string) $data->getData('image2') : null;
    }

    /**
     * Return review HTML (decoded) or null
     */
    public function getReview(): ?string
    {
        $data = $this->getModelData();
        if (! $data) {
            return null;
        }

        $raw = (string) $data->getData('review');

        // If saved as encoded entities in DB, decode safely
        // Note: If you plan to allow Magento directives ({{widget ...}}), use a FilterProvider in the block/viewmodel.
        return html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Title or null
     */
    public function getTitle(): ?string
    {
        $data = $this->getModelData();
        return $data ? (string) $data->getData('title') : null;
    }

    /**
     * Review writer or null
     */
    public function getReviewWriter(): ?string
    {
        $data = $this->getModelData();
        return $data ? (string) $data->getData('review_writer') : null;
    }

    /**
     * Load and return all testimonial items as an array of DataObject.
     *
     * @return DataObject[]
     */
    public function getAllTestimonials(): array
    {
        $collection = $this->testimonialsFactory->create()->getCollection();
        // Optionally, you can add filters or ordering here if needed
        return $collection->getItems();
    }


    
    /**
     * Check if the current product ID matches any ID in the given array.
     *
     * @param array $productIds
     * @return bool
     */
    public function isCurrentProductInList()
    {
        
        $currentProduct = $this->registry->registry('current_product');
        
        $currentProductId = (int)$currentProduct->getId();
        $checkProductIds = [9567,9443,9441,9439,9405,9395,9338,9337,9305, 9232, 9221,8294 ,2703 ];
        return in_array($currentProductId, $checkProductIds, true);

        
    }
}
