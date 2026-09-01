<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Nilesh\PrescriberName\Ui\Component\Listing\Column;
use \Nilesh\PrescriberName\Model\PrescriberNameFactory;

/**
 * Class Options
 */
class PrescriberNameOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var class
     */
    protected $_prescriberNameFactory;
    protected $logger;

    /**
     * Constructor
     *
     * @param 
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PrescriberNameFactory $prescriberNameFactory
    ) {
        $this->logger = $logger;
        $this->_prescriberNameFactory = $prescriberNameFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $model = $this->_prescriberNameFactory->create();
        $collection = $model->getCollection();
        try {
            foreach ($collection->getData() as $row) {
                // \print_r($row); exit();
                $this->options[] = array(
                    "label" => $row['name'],
                    "value" => (string) $row['prescribername_id']
                );
            }
        } catch (\Throwable $th) {
            $this->logger->addDebug($th);
        }
        // $this->logger->critical(\json_encode($this->options));
        // return \asort($this->options);
        return $this->options;
    }
}
