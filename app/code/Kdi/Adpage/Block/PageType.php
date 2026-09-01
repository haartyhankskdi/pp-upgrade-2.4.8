<?php
namespace Kdi\Adpage\Block;

use Magento\Framework\View\Element\Template;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class PageType extends Template
{
    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        Template\Context $context,
        PageRepositoryInterface $pageRepository,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->pageRepository = $pageRepository;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get custom field value (kdi_ad_type)
     */
    public function getKdiAdType($pageId)
    {
        if (!$pageId) {
            return null;
        }
        try {
            $page = $this->pageRepository->getById($pageId);
            return $page->getData('kdi_ad_type');
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
