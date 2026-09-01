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
class Index implements ArgumentInterface
{
    private TestimonialsFactory $testimonialsFactory;
    private RequestInterface $request;

    /**
     * Cached loaded model instance (or null if not loaded)
     *
     * @var DataObject|null
     */
    private ?DataObject $model = null;

    public function __construct(
        TestimonialsFactory $testimonialsFactory,
        RequestInterface $request
    ) {
        $this->testimonialsFactory = $testimonialsFactory;
        $this->request = $request;
    }

    /**
     * Get all testimonial items as collection
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getAllTestimonials()
    {
        return $this->testimonialsFactory->create()->getCollection();
    }

}