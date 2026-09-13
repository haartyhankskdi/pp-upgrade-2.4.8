<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Answser;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Answer\GetAttachedFileUrlInterface;
use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Config\Source\Status;
use Amasty\Customform\Model\ConfigProvider;
use Amasty\Customform\ViewModel\Answser\CustomerAccount\Answer\View\CurrentAnswerProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AnswerIndex implements ArgumentInterface
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var GetAttachedFileUrlInterface
     */
    private $getAttachedFileUrl;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CurrentAnswerProvider
     */
    private $currentFormProvider;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        Escaper $escaper,
        ConfigProvider $configProvider,
        GetAttachedFileUrlInterface $getAttachedFileUrl,
        Serializer $serializer,
        ProductRepositoryInterface $productRepository,
        CurrentAnswerProvider $currentFormProvider,
        UrlInterface $urlBuilder
    ) {
        $this->escaper = $escaper;
        $this->configProvider = $configProvider;
        $this->getAttachedFileUrl = $getAttachedFileUrl;
        $this->serializer = $serializer;
        $this->productRepository = $productRepository;
        $this->currentFormProvider = $currentFormProvider;
        $this->urlBuilder = $urlBuilder;
    }

    public function getCurrentResponse(): AnswerInterface
    {
        return $this->currentFormProvider->getCurrentResponse();
    }

    public function getAdminResponseData(): array
    {
        $model = $this->getCurrentResponse();
        $responseStatus = $model->getAdminResponseStatus();
        $status = $responseStatus == Status::ANSWERED ? __('Sent') : __('Pending');
        $result = [['label' => __('Response Status'), 'value' => $status]];

        if ($responseStatus) {
            $result[] = [
                'label' => __('Recipient'),
                'value' => $model->getAdminResponseEmail()
            ];
            $result[] = [
                'label' => __('Response Message'),
                'value' => $this->escaper->escapeHtml($model->getResponseMessage())
            ];
        }

        return $result;
    }

    public function getGoogleKey(): string
    {
        return $this->configProvider->getGoogleKey();
    }

    private function getFileDownloadMarkup($value): string
    {
        $url = $this->escaper->escapeUrl($this->getAttachedFileUrl->execute($value));

        return sprintf(
            '<a href="%s">%s</a>',
            $url,
            __('Download: %1', $value)->render()
        );
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        $model = $this->getCurrentResponse();
        $result = [];
        $formData = $model->getResponseJson();

        if ($formData) {
            $fields = $this->serializer->unserialize($formData);

            foreach ($fields as $name => $field) {
                $result[] = [
                    'label' => $field['label'],
                    'value' => $this->parseFieldValue($field, $name),
                    'type' => $field['type']
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $field
     * @param string $name
     * @return string
     */
    private function parseFieldValue($field, $name)
    {
        $value = $field['value'];

        if (is_array($value)) {
            if (in_array([], $value)) {
                $emptyArrayKey = array_search([], $value);
                unset($value[$emptyArrayKey]);
            }

            $value = implode(', ', $value);
        }

        switch ($field['type']) {
            case 'googlemap':
                // dont use escape for json
                break;
            case 'file':
                $value = $this->parseFileField($field, $value);
                break;
            default:
                $value = $this->escaper->escapeHtml($value);
        }

        if ($name == 'hide_product_id') {
            try {
                $product = $this->productRepository->getById($value);
                $value = sprintf(
                    '<a href="%s">%s</a>',
                    $this->urlBuilder->getUrl('catalog/product/edit', ['id' => $product->getId()]),
                    $product->getName()
                );
            } catch (\Exception $ex) {
                $product = null;
            }
        }

        return $value;
    }

    private function parseFileField(array $field, string $value): string
    {
        if (is_array($field['value'])) {
            $fileMarkup = '';

            foreach ($field['value'] as $item) {
                if (is_array($item) && empty($item)) {
                    $emptyFieldValueKey = array_search([], $field['value']);
                    unset($field['value'][$emptyFieldValueKey]);
                    continue;
                }

                $itemMarkup = $this->getFileDownloadMarkup($item);
                $fileMarkup .= $itemMarkup . PHP_EOL;
            }

            $value = nl2br($fileMarkup);
        } else {
            $value = $this->getFileDownloadMarkup($value);
        }
        return $value;
    }
}
