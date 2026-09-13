<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Form;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

interface FormInitInterface extends ArgumentInterface
{
    /**
     * @return FormInterface|null
     */
    public function getCurrentForm(): ?FormInterface;

    /**
     * @return bool
     */
    public function isSurvey(): bool;

    /**
     * @return string
     */
    public function getFormAction(): string;

    /**
     * @return string
     */
    public function getGDPRText(): string;

    /**
     * @return bool
     */
    public function isUseGoogleMap(): bool;

    /**
     * @param bool $useGoogleMap
     */
    public function setUseGoogleMap(bool $useGoogleMap): void;

    /**
     * @return string
     */
    public function getGoogleKey(): string;

    /**
     * @return string
     */
    public function getPopupButtonTitle(): string;

    /**
     * @return string
     */
    public function getFormKey(): string;

    /**
     * @return string
     */
    public function getFormParamsJson(): string;

    /**
     * @return int
     */
    public function getFormId(): int;

    /**
     * @return string
     */
    public function getButtonTitle(): string;

    /**
     * @return bool
     */
    public function isPopupUsed(): bool;

    /**
     * @return string
     */
    public function getFormFillerComponent(): string;

    /**
     * @return bool
     */
    public function isGDPREnabled(): bool;

    /**
     * @param string $scope
     *
     * @return string
     */
    public function getGdprCheckboxHtml(string $scope): string;
}
