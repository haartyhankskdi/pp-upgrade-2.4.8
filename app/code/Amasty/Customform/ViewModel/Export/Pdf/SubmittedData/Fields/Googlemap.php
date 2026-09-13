<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Model\Answer\GoogleMap\Api\GetAddressAndImage;
use Magento\Framework\View\Asset\Repository as AssetsRepository;

class Googlemap implements FieldValueInterface
{
    public const IMAGE = 'image';
    public const ADDRESS = 'address';
    public const PLACEHOLDER_IMAGE = 'Magento_Catalog::images/product/placeholder/small_image.jpg';

    use FieldViewModelTrait;

    /**
     * @var string
     */
    private $fieldValue;

    /**
     * @var GetAddressAndImage
     */
    private $getAddressAndImage;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var AssetsRepository
     */
    private $assetRepository;

    public function __construct(
        GetAddressAndImage $getAddressAndImage,
        Serializer $serializer,
        AssetsRepository $assetRepository
    ) {
        $this->getAddressAndImage = $getAddressAndImage;
        $this->serializer = $serializer;
        $this->assetRepository = $assetRepository;
    }

    public function getFieldValue(): array
    {
        try {
            $googleMapConfig = $this->serializer->unserialize($this->fieldValue);

            if (isset($googleMapConfig['position']['lat']) && isset($googleMapConfig['position']['lng'])) {
                $longitude = (float) $googleMapConfig['position']['lng'];
                $latitude = (float) $googleMapConfig['position']['lat'];
                $apiResult = $this->getAddressAndImage->execute($longitude, $latitude);

                $result = [
                    self::ADDRESS => $apiResult->getAddress(),
                    self::IMAGE => $apiResult->getImage()
                ];
            } else {
                $result = [
                    self::ADDRESS => __('Invalid Answer Data'),
                    self::IMAGE => $this->getPlaceholderImageUrl()
                ];
            }
        } catch (\Exception $e) {
            $result = [
                self::ADDRESS => __('Google Api error'),
                self::IMAGE => $this->getPlaceholderImageUrl()
            ];
        }

        return $result;
    }

    private function getPlaceholderImageUrl(): string
    {
        return $this->assetRepository->getUrl(self::PLACEHOLDER_IMAGE);
    }
}
