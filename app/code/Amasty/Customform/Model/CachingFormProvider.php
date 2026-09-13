<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CachingFormProvider
{
    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    /**
     * @var FormInterface[]
     */
    private $cache = [];

    /**
     * @var FormInterface[]
     */
    private $cacheByCode = [];

    public function __construct(
        FormRepositoryInterface $formRepository
    ) {
        $this->formRepository = $formRepository;
    }

    public function getById(int $formId): ?FormInterface
    {
        if (!array_key_exists($formId, $this->cache)) {
            try {
                $form = $this->formRepository->get($formId);
                $this->cache[$formId] = $form;
                $this->cacheByCode[$form->getCode()] = $form;
            } catch (NoSuchEntityException $e) {
                $this->cache[$formId] = null;
            }
        }

        return $this->cache[$formId];
    }

    public function getByCode(string $formCode): ?FormInterface
    {
        if (!array_key_exists($formCode, $this->cacheByCode)) {
            try {
                $form = $this->formRepository->getByFormCode($formCode);
                $this->cache[$form->getFormId()] = $form;
                $this->cacheByCode[$formCode] = $form;
            } catch (NoSuchEntityException $e) {
                $this->cacheByCode[$formCode] = null;
            }
        }

        return $this->cacheByCode[$formCode];
    }

    public function _resetState(): void
    {
        $this->cache = [];
        $this->cacheByCode = [];
    }
}
