<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Captcha\Rewrite\Magento\Captcha\Model;

class DefaultModel extends \Magento\Captcha\Model\DefaultModel
{

public function __construct(
    \Magento\Framework\Session\SessionManagerInterface $session,
    \Magento\Captcha\Helper\Data $captchaData,
    \Magento\Captcha\Model\ResourceModel\LogFactory $resLogFactory,
    $formId
  ) 
  {
    parent::__construct($session,$captchaData,$resLogFactory,$formId);
    $this->setDotNoiseLevel(50);
    $this->setLineNoiseLevel(0); 
  } 

}

