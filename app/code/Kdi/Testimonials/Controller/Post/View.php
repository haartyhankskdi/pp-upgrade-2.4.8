<?php
declare(strict_types=1);

namespace Kdi\Testimonials\Controller\Post;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Kdi\Testimonials\Model\TestimonialsFactory;

/**
 * Class View
 *
 * Frontend controller to display a single testimonial
 * URL example:
 * successstory/post/view?url=my-success-story
 */
class View implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $pageFactory;

    /** @var TestimonialsFactory */
    private $testimonialsFactory;

    /** @var RedirectFactory */
    private $redirectFactory;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var Registry */
    private $registry;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var LoggerInterface */
    private $logger;

    private $request;

    /**
     * View constructor.
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        TestimonialsFactory $testimonialsFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        Registry $registry,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->pageFactory        = $pageFactory;
        $this->testimonialsFactory = $testimonialsFactory;
        $this->redirectFactory    = $redirectFactory;
        $this->messageManager     = $messageManager;
        $this->registry           = $registry;
        $this->urlBuilder         = $urlBuilder;
        $this->logger             = $logger;
        $this->request = $request;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $slug= $this->request->getParam('url');

            // echo 'slug'. $slug;
            // exit();
            if (!$slug) {
                return $this->redirectToListing();
            }

            /** Load testimonial by SEO URL */
            $model = $this->testimonialsFactory->create()
                ->load($slug, 'meta_url');

            // if (!$model->getId() || !$model->getStatus()) {
            if (!$model->getId()) {
                return $this->redirectToListing(
                    __('The testimonial you are looking for does not exist.' . '/successstory/post/' .$slug )
                );
            }

            /** Register for blocks */
            if (!$this->registry->registry('success_story')) {
                $this->registry->register('success_story', $model);
            }

            /** Create result page */
            $resultPage = $this->pageFactory->create();

            /** Apply SEO meta */
            $this->applyMetaData($resultPage, $model);

            return $resultPage;

        } catch (\Throwable $e) {
            $this->logger->critical($e);
            return $this->redirectToListing(
                __('Something went wrong while loading testimonial.')
            );
        }
    }

    /**
     * Apply SEO & Social Meta Tags
     */
    private function applyMetaData($resultPage, $testimonial): void
    {
        $config = $resultPage->getConfig();

        $title       = $testimonial->getMetaTitle() ?: $testimonial->getTitle();
        $description = $testimonial->getMetaDesc() ?: '';
        $robots      = $testimonial->getRobots() ? 'index,follow' : 'noindex,nofollow';
        $pageUrl = $this->urlBuilder->getCurrentUrl();
        $config->addRemotePageAsset(
           $pageUrl,
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );

        $config->getTitle()->set($title);
        $config->setDescription($description);
        $config->setMetadata('robots', $robots);

        $this->applyOpenGraphTags($config, $testimonial);
    }

    /**
     * Apply OpenGraph Meta Tags
     */
    private function applyOpenGraphTags($config, $testimonial): void
    {
        $imageUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
            . ltrim((string)$testimonial->getImage1(), '/');

        $pageUrl = $this->urlBuilder->getCurrentUrl();

        $config->setMetadata('og:title', $testimonial->getTitle());
        $config->setMetadata('og:description', $testimonial->getMetaDesc());
        $config->setMetadata('og:image', $imageUrl);
        $config->setMetadata('og:url', $pageUrl);
        $config->setMetadata('og:type', 'article');
    }

    /**
     * Redirect to Testimonials Listing
     */
    private function redirectToListing($message = null)
    {
        if ($message) {
            $this->messageManager->addWarningMessage($message);
        }

        $redirect = $this->redirectFactory->create();
        $redirect->setUrl($this->urlBuilder->getUrl('successstory'));

        return $redirect;
    }
}
