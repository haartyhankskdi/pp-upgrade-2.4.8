<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Block;

use Magento\Framework\View\Element\Template;
use AutifyDigital\LloydscardnetPayment\Helper\Data;

/**
 * Class Lcnetpaymentjs
 * Responsible for retrieving saved cards of the customer and providing
 * a delete URL for each card.
 */
class Lcnetpaymentjs extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * SavedCards constructor.
     *
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve payment mode
     *
     * @return String
     */
    public function getMode()
    {
        return $this->helper->getMode();
    }

    /**
     * Retrieve Library
     *
     * @return String
     */
    public function getLibrary()
    {
        return $this->helper->getLibrary();
    }

    /**
     * Generate the delete URL for a specific saved card.
     *
     * @param int $tokenId
     * @return string
     */
    public function getDeleteUrl(int $tokenId): string
    {
        return $this->getUrl('lloyds/paymentjs/delete', ['id' => $tokenId]);
    }
}
