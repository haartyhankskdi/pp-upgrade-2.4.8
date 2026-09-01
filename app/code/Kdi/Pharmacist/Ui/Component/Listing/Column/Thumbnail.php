<?php
namespace Kdi\Pharmacist\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Thumbnail extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {

                $fieldName = $this->getData('image');
                // print_r($fieldName);die;
            
                // Check if the image path exists in the item
                if (isset($item[$fieldName]) && !empty($item[$fieldName])) {
                    echo $imageUrl = $this->getImageUrl($item[$fieldName]);
                } else {
                    $imageUrl = $this->getImageUrl('placeholder.jpg');
                }
                
                // Update the item with the HTML for the image
                $item[$fieldName] = '<img src="' . $imageUrl . '" alt="' . __('Thumbnail') . '" width="100" height="100"/>';
            }
        }

        return $dataSource;
    }

    private function getImageUrl($fileName)
    {
        // Manually set the base media URL
        $baseUrl = 'https://phpstack-732705-2678695.cloudwaysapps.com/pub/media/';
        
        // Return the URL for the file, or a placeholder if the filename is empty
        return $baseUrl . $fileName;
    }
    
}
