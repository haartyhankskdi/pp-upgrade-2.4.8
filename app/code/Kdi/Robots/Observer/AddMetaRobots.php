<?php
namespace Kdi\Robots\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\App\RequestInterface;

class AddMetaRobots implements ObserverInterface
{
    protected $pageConfig;
    protected $request;

    public function __construct(PageConfig $pageConfig, RequestInterface $request)
    {
        $this->pageConfig = $pageConfig;
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        
        $noIndexUrls = [
            'blueinhaler-ad',
            'ad-trim',
            'ad-asthma-copd-amino',
            'ad-acne',
            'lymtet-ad',
            'ad-anti-inflammatory-category',
            'etoarco-ad',
            'ad-aquacel-usa',
            'ad-asthma-copd',
            'ad-contraceptives',
            'ad-cystitis',
            'ad-derm',
            'ad-glic',
            'ad-diabetes-test-strips',
            'ad-diclo',
            'ad-dressings-cat',
            'ad-aquacel',
            'ad-ecz',
            'ad-ed-man',
            'ad-ed-sop',
            'ad-ed-pd',
            'ad-ed-lp',
            'ad-ed-dc',
            'ad-ed-sildenafil',
            'ad-ed-tadcia',
            'ad-eyes-ears-nose',
            'ad-fost',
            'ad-gurinary',
            'ad-haemorrhoids',
            'ad-recto',
            'ad-high-blood-pressure',
            'ad-high-cholesterol',
            'ad-hrt-evo',
            'adhrtinstockoest1',
            'ad-hrt-category',
            'ad-hrt-est',
            'ad-silvia-lloyds',
            'ad-dia',
            'ad-mi',
            'weightlosspen-mou',
            'weightloss',
            'ad-napr',
            'ad-ed-misc',
            'ad-alenacid',
            'ad-osteoporosis',
            'ad-ozempic-weight-loss',
            'ad-ozempic-weight-loss--63c57b949c404',
            'ad-php-ireland',
            'ad-asthma-inhalers',
            'ad-allergy-pens-for-schools',
            'ad-ed-silvia',
            'ad-thyroid',
            'ad-timo',
            'travel-clinic',
            'weightlosspen-65fc5973246d1',
            'ad-weight-loss',
            'join-the-pharmacy-planet-weight-loss-program-mnj',
            'join-the-pharmacy-planet-weight-loss-program-weg',
            'join-the-pharmacy-planet-weight-loss-program-ozp',
            'loseweight',
            'social-collaboration',
            'theme-1',
            'theme-2',
            'about-627b73c99f9dd',
            'fs3-pharmacy-planet',
            'ven-pharmacy-planet',
            'weight-loss',
            'newsletter-success',
            'ad-met',
            'ad-beatnuman',
            'ad-betno',
            'home'
        ];

        
        $currentPath = $this->request->getRequestUri();
        foreach ($noIndexUrls as $url) {
            if (strpos($currentPath, $url) !== false) {
                $this->pageConfig->setMetadata('robots', 'NOINDEX, NOFOLLOW');
                break; 
            }
        }
    }
}
