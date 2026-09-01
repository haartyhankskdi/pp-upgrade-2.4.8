<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Haartyhanks\CategoryQuestWL\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends Action
{
	protected $_pageFactory;
	private $customCookieManager;
	private $logger;

	private $productUrlMap = [
		'wegovy-injection-|-price-drop-|-from-£75'     => '/weightloss/buy-wegovy.html?true',
		'wegovy-tablets-for-weight-loss-|-from-£90-|-oral-semaglutide' => '/weightloss/buy-wegovy-tab.html?true',
		'mounjaro'   => '/weightloss/buy-mounjaro.html?true',
		'orlistat/xenical-capsules'   => '/weightloss/buy-orlistat-xenical-capsules.html?true',
		'mysimba'   => '/weightloss/buy-mysimba.html?true?true',
		'foundayo-(orforglipron)-weight-loss-pill' => '/weightloss/buy-foundayo-orforglipron.html?true'

	];

	public function __construct(
		Context $context,
		PageFactory $pageFactory,
		CookieManagerInterface $customCookieManager,
		LoggerInterface $logger
	) {
		$this->_pageFactory        = $pageFactory;
		$this->customCookieManager = $customCookieManager;
		$this->logger              = $logger;
		parent::__construct($context);
	}

	public function execute()
	{
		try {
			$product = $this->getCookie();

			if ($product) {
				$productUrl = $this->getProductUrl($product);

				if ($productUrl) {
					$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
					$resultRedirect->setUrl($productUrl);
					return $resultRedirect;
				}

				$this->logger->warning(
					'CategoryQuestWL: No URL mapped for product cookie value.',
					['product' => $product]
				);
			}
		} catch (\Exception $e) {
			$this->logger->error(
				'CategoryQuestWL: Error in Index controller.',
				['message' => $e->getMessage()]
			);
		}

		return $this->_pageFactory->create();
	}

	private function getCookie(): string
	{
		return (string) $this->customCookieManager->getCookie('preselected_product', '');
	}

	private function getProductUrl(string $product): ?string
	{
		return $this->productUrlMap[$product] ?? null;
	}
}
