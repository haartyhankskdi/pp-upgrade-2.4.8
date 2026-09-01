<?php

declare(strict_types=1);

namespace Haartyhanks\AuthReview\Plugin\Cms\Helper;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Helper\Page;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Breadcrumbs;
use Magento\Framework\App\RequestInterface;

class PagePlugin
{
    /**
     * Custom breadcrumb configuration by CMS page identifier.
     */
    private const BREADCRUMB_CONFIG = [
        'how-much-do-wegovy-tablets-cost' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'can-you-switch-to-pharmacy-planet-from-another-provider' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'can-you-switch-to-wegovy-tablets-from-weight-loss-injections' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'eating-less-vs-eating-well-why-nutrition-matters' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'what-happens-when-you-stop-taking-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'why-choose-pharmacy-planet-for-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'losing-weight-vs-looking-your-best-why-exercise-matters' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'how-much-does-mounjaro-cost' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'mounjaro',
                'label' => 'Mounjaro',
                'link'  => '/mounjaro.html'
            ]
        ],
        'side-effects' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'what-is-it-and-how-does-it-work' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'how-to-take-it' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'eligibility' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'weight-loss-results' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'is-it-right-for-me' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'side-effects-of-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'what-are-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'how-much-weight-do-you-lose-on-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ],
        'what-are-the-eligibility-requirements-for-wegovy-tablets' => [
            [
                'name'  => 'general-health',
                'label' => 'General Health',
                'link'  => '/general-health/weight-loss.html'
            ],
            [
                'name'  => 'weight-pill',
                'label' => 'Wegovy Pill',
                'link'  => '/wegovy-tablets-semaglutide-weight-loss-pills.html'
            ]
        ]


    ];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    protected $request;

    /**
     * @param StoreManagerInterface $storeManager
     * @param PageRepositoryInterface $pageRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        PageRepositoryInterface $pageRepository,
        RequestInterface $request
    ) {
        $this->storeManager = $storeManager;
        $this->pageRepository = $pageRepository;
        $this->request = $request;
    }

    /**
     * Replace breadcrumbs for configured CMS pages.
     *
     * @param Page $subject
     * @param ResultPage $resultPage
     * @param int $pageId
     * @return ResultPage
     */
    public function afterPrepareResultPage(
        Page $subject,
        ResultPage $resultPage,
        $pageId
    ): ResultPage {
        $pageId = (int) $this->request->getParam('page_id');
        if ($pageId == 0) {
            return $resultPage;
        }
        $page = $this->pageRepository->getById($pageId);
        $identifier = $page->getIdentifier();



        if (!isset(self::BREADCRUMB_CONFIG[$identifier])) {

            return $resultPage;
        }

        /** @var Breadcrumbs|null $breadcrumbs */
        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');

        if ($breadcrumbs === null) {
            return $resultPage;
        }

        $this->clearBreadcrumbs($breadcrumbs);

        // Home
        $breadcrumbs->addCrumb('home', [
            'label' => __('Home'),
            'title' => __('Home'),
            'link'  => $this->storeManager->getStore()->getBaseUrl()
        ]);

        // Custom Breadcrumbs
        foreach (self::BREADCRUMB_CONFIG[$identifier] as $crumb) {
            $breadcrumbs->addCrumb($crumb['name'], [
                'label' => __($crumb['label']),
                'title' => __($crumb['label']),
                'link'  => $crumb['link']
            ]);
        }

        // Current CMS Page
        $breadcrumbs->addCrumb('current', [
            'label' => __($page->getTitle()),
            'title' => __($page->getTitle())
        ]);

        return $resultPage;
    }

    /**
     * Clear all existing breadcrumbs.
     *
     * @param Breadcrumbs $breadcrumbs
     * @return void
     */
    private function clearBreadcrumbs(Breadcrumbs $breadcrumbs): void
    {
        $reflection = new \ReflectionClass($breadcrumbs);
        $property = $reflection->getProperty('_crumbs');
        $property->setAccessible(true);
        $property->setValue($breadcrumbs, []);
    }
}
