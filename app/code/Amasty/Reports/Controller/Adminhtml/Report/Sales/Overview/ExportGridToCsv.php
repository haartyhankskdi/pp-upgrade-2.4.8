<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Sales\Overview;

use Amasty\Reports\Model\Grid\Export\Sales\Overview\ConvertToCsv;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Ui\Controller\Adminhtml\Export\GridToCsv;

class ExportGridToCsv extends GridToCsv
{
    public function __construct(
        Context $context,
        ConvertToCsv $converter,
        FileFactory $fileFactory,
        $filter = null,
        $logger = null
    ) {
        parent::__construct($context, $converter, $fileFactory);
    }
}
