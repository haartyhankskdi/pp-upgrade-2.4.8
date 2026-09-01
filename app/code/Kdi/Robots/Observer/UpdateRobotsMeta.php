<?php

namespace Kdi\Robots\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class UpdateRobotsMeta implements ObserverInterface
{
    /**
     * @var PageConfig
     */
    private $pageConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * List of URLs requiring "NOINDEX, NOFOLLOW"
     * 
     * @var array
     */
    private $noIndexUrls = [
            'https://www.pharmacyplanet.com/womens-health/contraceptive/millinette-tablets.html',
            'https://www.pharmacyplanet.com/general-health/high-cholesterol/simvastatin-tablets.html',
            'https://www.pharmacyplanet.com/general-health/high-cholesterol/atorvastatin.html',
            'https://www.pharmacyplanet.com/general-health/flu/tamiflu-tablets.html',
            'https://www.pharmacyplanet.com/general-health/high-blood-pressure/carvedilol.html',
            'https://www.pharmacyplanet.com/sexual-health/aldara-cream.html',
            'https://www.pharmacyplanet.com/womens-health/hrt/evorel-patches.html',
            'https://www.pharmacyplanet.com/travel/jet-lag/circadin-melatonin.html',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/duac-gel.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/levest-tablets.html',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/mirvaso-gel.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/femodene-ed-tablets.html',
            'https://www.pharmacyplanet.com/general-health/eczema-psoriasis/betnovate.html',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/oxytetracycline-tablets.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/feanolla-tablets.html',
            'https://www.pharmacyplanet.com/general-health/eczema-psoriasis/eumovate.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/logynon.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/millinette-tablets.html',
            'https://www.pharmacyplanet.com/general-health/high-blood-pressure/irbesartan-tablets.html',
            'https://www.pharmacyplanet.com/general-health/haemorrhoids/scheriproct-ointment.html',
            'https://www.pharmacyplanet.com/general-health/migraine-treatment/imigran-sumatriptan-tablets.html',
            'https://www.pharmacyplanet.com/haemorrhoids/proctosedyl-ointment.html',
            'https://www.pharmacyplanet.com/womens-health/contraceptive/cerazette-tablets.html',
            'https://www.pharmacyplanet.com/mens-health/hair-loss/propecia-finasteride-for-hair-loss.html',
            'https://www.pharmacyplanet.com/womens-health/hrt/ovestin-cream.html',
            'https://www.pharmacyplanet.com/general-health/allergy/telfast-fexofenadine-tablets.html',
            'https://www.pharmacyplanet.com/general-health/high-blood-pressure/sotalol-tablets.html',
            'https://www.pharmacyplanet.com/womens-health/hrt/estraderm-mx-patches.html',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/tetralysal-lymecycline.html',
            'https://www.pharmacyplanet.com/womens-health/hrt/elleste-duet-tablets.html',
            'https://www.pharmacyplanet.com/general-health/anti-inflammatories/naproxen-500-mg-naproxen-250-mg-naproxen-buy-naproxen-naproxen-dosage-naproxen-tablets-online-tablets-for-swelling-best-medicine-for-swelling-and-pain-best-medicine-for-joint-pain-body-pain-tablet-tablets-for-muscle-pain',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/rozex.html',
            'https://www.pharmacyplanet.com/general-health/acne-rosacea/differin.html',
            'https://www.pharmacyplanet.com/womens-health/hrt/oestrogel.html'
        ];

    /**
     * Constructor
     *
     * @param PageConfig $pageConfig
     * @param RequestInterface $request
     * @param UrlInterface $url
     */
    public function __construct(
        PageConfig $pageConfig,
        RequestInterface $request,
        UrlInterface $url
    ) {
        $this->pageConfig = $pageConfig;
        $this->request = $request;
        $this->url = $url;
    }

    /**
     * Execute observer logic
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if the event is triggered on a catalog product view page
        $fullActionName = $observer->getEvent()->getFullActionName();
        if ($fullActionName !== 'catalog_product_view') {
            return;
        }

        // Get the current URL and request parameters
        $currentUrl = $this->url->getCurrentUrl();
        $params = $this->request->getParams();

        // Apply "NOINDEX, NOFOLLOW" if the URL matches or a specific parameter exists
        if ($this->isNoIndexUrl($currentUrl) || isset($params['true'])) {
            $this->pageConfig->setMetadata('robots', 'NOINDEX, FOLLOW');
        }
    }

    /**
     * Check if the given URL matches any of the configured noindex URLs
     *
     * @param string $currentUrl
     * @return bool
     */
    private function isNoIndexUrl(string $currentUrl): bool
    {
        return in_array(rtrim($currentUrl, '/'), $this->noIndexUrls, true);
    }
}
