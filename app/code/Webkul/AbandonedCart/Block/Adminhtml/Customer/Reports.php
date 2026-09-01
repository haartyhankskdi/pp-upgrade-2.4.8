<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Block\Adminhtml\Customer;

class Reports extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Webkul\AbandonedCart\Model\MailsLog $mailslog,
     * @param \Magento\Quote\Model\Quote $quoteModel,
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $registry
     * @param array $data = []
     **/
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Webkul\AbandonedCart\Model\MailsLog $mailslog,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\Quote $quoteModel,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_mailsLog = $mailslog;
        $this->_localeDate = $localeDate;
        $this->_messageManager = $messageManager;
        $this->_quoteModel = $quoteModel;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_blockGroup = 'Webkul_AbandonedCart';
        $this->_controller = 'adminhtml_customer_cart_details';
        parent::_construct();
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        $class = "";
        if ($this->getRequest()->getParam('limit') == "yearly") {
            $class = "abandoned-cart-button-admin";
        }
        $this->buttonList->add(
            'yearlyview',
            [
                'label' =>  __('Current Year Stats'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('abandonedcart/customer/reports/limit/yearly') . '\')',
                'class'     =>  $class
            ],
            100
        );
        $class = "";
        if ($this->getRequest()->getParam('limit') == "monthly") {
            $class = "abandoned-cart-button-admin";
        }
        $this->buttonList->add(
            'monthlyView',
            [
                'label' =>  __('Current Month Stats'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('abandonedcart/customer/reports/limit/monthly') . '\')',
                'class'     =>  $class
            ],
            100
        );
        $class = "";
        if ($this->getRequest()->getParam('limit') == "") {
            $class = "abandoned-cart-button-admin";
        }
        $this->buttonList->add(
            'Daily View',
            [
                'label' =>  __('Current Week Stats'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('abandonedcart/customer/reports') . '\')',
                'class'     =>  $class
            ],
            100
        );
        $this->buttonList->remove('back');
    }

    /**
     * Get data set
     *
     * @return array
     */
    public function getDatasets()
    {
        $post = $this->getRequest()->getParams();
        $limit = $this->getRequest()->getParam('limit');
        if ($limit == "") {
            if (date('N')==1) {
                $firstDate = date('Y-m-d');
            } else {
                $firstDate = date('Y-m-d', strtotime("last monday"));
            }
            $lastDate = date('Y-m-d', strtotime($firstDate." +6 days"));
            $count = 7;
            $abandonedCartData = $this->getAbandonedCartData($firstDate, $lastDate, $count);
            $sentMailsData = $this->getSentMails($firstDate, $lastDate, $count);
            $convertedData = $this->getRecoveredData($firstDate, $lastDate, $count);

            return $this->prepareDataSet($abandonedCartData, $sentMailsData, $convertedData);
        } else {
            if ($limit == "monthly") {
                //calculating abandoned carts for last month
                $firstDate = date('Y-m-01');
                $lastDate = date('Y-m-30');
                $count = 30;
                $abandonedCartData = $this->getAbandonedCartData($firstDate, $lastDate, $count);
                $sentMailsData = $this->getSentMails($firstDate, $lastDate, $count);
                $convertedData = $this->getRecoveredData($firstDate, $lastDate, $count);

                return $this->prepareDataSet($abandonedCartData, $sentMailsData, $convertedData);
            }
            if ($limit == "yearly") {
                $firstDate = date('Y-01-01');
                $lastDate = date('Y-12-30');
                $count = 12;
                $abandonedCartData = $this->getAbandonedCartData($firstDate, $lastDate, $count);
                $sentMailsData = $this->getSentMails($firstDate, $lastDate, $count);
                $convertedData = $this->getRecoveredData($firstDate, $lastDate, $count);

                return $this->prepareDataSet($abandonedCartData, $sentMailsData, $convertedData);
            }
        }
    }

    /**
     * @return string
     */
    public function getLabels()
    {
        $limit = $this->getRequest()->getParam('limit');
        if ($limit == "") {
            $monday = strtotime("last monday");
            $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
            $data = [];
            $counter = 0;
            while ($counter <= 6) {
                $tempday = strtotime(date("Y-m-d", $monday)." +".$counter." days");
                $date = date("d", $tempday)."/".date("M", $tempday);
                array_push($data, $date);
                $counter++;
            }
            return json_encode($data);
        } else {
            if ($limit == "monthly") {
                $first = strtotime(date('01-m-Y'));
                $data = [];
                $counter = 0;
                while ($counter < 30) {
                    $tempday = strtotime(date("Y-m-d", $first)." +".$counter." days");
                    $date = date("d", $tempday)."/".date("M", $tempday);
                    array_push($data, $date);
                    $counter++;
                }
                return json_encode($data);
            }
            if ($limit == "yearly") {
                $data = [
                     'Jan', 'Feb', 'Mar',
                     'Apr', 'May', 'Jun',
                     'Jul', 'Aug', 'Sep',
                     'Oct', 'Nov', 'Dec'
                ];
                return json_encode($data);
            }
        }
    }

    /**
     * get abandoned cart data
     *
     * @param Date $firstDate
     * @param Date $lastDate
     * @param int $count
     **/
    public function getAbandonedCartData($firstDate, $lastDate, $count)
    {
        $data = $this->_quoteModel->getCollection()
                                    ->addFieldToFilter('updated_at', ['gt' => $firstDate])
                                    ->addFieldToFilter('updated_at', ['lt' => $lastDate])
                                    ->addFieldToFilter('customer_email', ['notnull' => true])
                                    ->addFieldToFilter('items_count', ['gt' => 0])
                                    ->addFieldToFilter('customer_firstname', ['notnull' => true])
                                    ->addFieldToFilter('reserved_order_id', ['null' => true]);

        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            $data->getSelect()->group("MONTH(`updated_at`)")
                                ->reset("columns")->columns("MONTH(`updated_at`) as date")
                                ->columns("COUNT(MONTH(`updated_at`)) as count");
        } else {
            $data->getSelect()->group("CAST(`updated_at` AS DATE)")
                                ->reset("columns")->columns("CAST(`updated_at` AS DATE) as date")
                                ->columns("COUNT('updated_at') as count");
        }

        $data = $data->getData();
        $datasource = [];
        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            foreach ($data as $dat) {
                $month = $dat['date'];
                $datasource[$month] = $dat['count'];
            }
        } else {
            foreach ($data as $dat) {
                $date = (int) substr($dat['date'], 8, 2);
                $datasource[$date] = $dat['count'];
            }
        }
        $final = [];
        $first = (int) substr($firstDate, 8, 2);
        for ($i = $first; $i < ($first + $count); $i++) {
            if (!isset($datasource[$i])) {
                $datasource[$i] = "0";
            }
            $final[] = $datasource[$i];
        }
        return json_encode($final);
    }

    /**
     * Get sent mails
     *
     * @param int $firstDate
     * @param timstamp $lastDate
     * @param timestamp $count
     * @return string
     */
    public function getSentMails($firstDate, $lastDate, $count)
    {
        $mailsData = $this->_mailsLog->getCollection()
                            ->addFieldToFilter('sent_on', ['gt' => $firstDate])
                            ->addFieldToFilter('sent_on', ['lt' => $lastDate]);

        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            $mailsData->getSelect()->group("MONTH(`sent_on`)")
                        ->reset("columns")->columns("COUNT(MONTH(`sent_on`)) as count")
                        ->columns("MONTH(`sent_on`) as date");
        } else {
            $mailsData->getSelect()->group("CAST(`sent_on` AS DATE)")
                            ->reset("columns")->columns("COUNT('sent_on') as count")
                            ->columns("CAST(`sent_on` AS DATE) as date");
        }
        $mailsData = $mailsData->getData();

        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            foreach ($mailsData as $dat) {
                $month = $dat['date'];
                $datasource[$month] = $dat['count'];
            }
        } else {
            foreach ($mailsData as $dat) {
                $date = (int) substr($dat['date'], 8, 2);
                $datasource[$date] = $dat['count'];
            }
        }
        $final = [];
        $first = (int) substr($firstDate, 8, 2);
        for ($i = $first; $i < ($first + $count); $i++) {
            if (!isset($datasource[$i])) {
                $datasource[$i] = "";
            }
            array_push($final, $datasource[$i]);
        }
        return json_encode($final);
    }

    /**
     * Get recovered data
     *
     * @param timestamp $firstDate
     * @param timestamp $lastDate
     * @param int $count
     * @return string
     */
    public function getRecoveredData($firstDate, $lastDate, $count)
    {
        $logQuery = $this->_mailsLog->getCollection()
                                    ->addFieldToFilter('sent_on', ['gt' => $firstDate])
                                    ->addFieldToFilter('sent_on', ['lt' => $lastDate])
                                    ->getSelect()->reset("columns")->columns('quote_id')
                                    ->__toString();

        $recoveredData = $this->_quoteModel->getCollection()
                                            ->addFieldToFilter('updated_at', ['gt' => $firstDate])
                                            ->addFieldToFilter('updated_at', ['lt' => $lastDate])
                                            ->addFieldToFilter('customer_email', ['notnull' => true])
                                            ->addFieldToFilter('customer_firstname', ['notnull' => true])
                                            ->addFieldToFilter('reserved_order_id', ['notnull' => true]);

        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            $recoveredData ->getSelect()->group("CAST(`updated_at` AS DATE)")
                            ->reset("columns")->columns("MONTH(`updated_at`) as date")
                            ->columns("COUNT(MONTH(`updated_at`)) as count");
        } else {
            $recoveredData ->getSelect()->group("CAST(`updated_at` AS DATE)")
                            ->reset("columns")->columns("CAST(`updated_at` AS DATE) as date")
                            ->columns("COUNT('updated_at') as count");
        }

        $recoveredData ->getSelect()->where("main_table.entity_id in ($logQuery)");
        $recoveredData = $recoveredData->getData();

        if ($limit = $this->getRequest()->getParam('limit') == "yearly") {
            foreach ($recoveredData as $dat) {
                $month = $dat['date'];
                $datasource[$month] = $dat['count'];
            }
        } else {
            foreach ($recoveredData as $dat) {
                $date = (int) substr($dat['date'], 8, 2);
                $datasource[$date] = $dat['count'];
            }
        }

        $final = [];
        $first = (int) substr($firstDate, 8, 2);
        for ($i = $first; $i < ($first + $count); $i++) {
            if (!isset($datasource[$i])) {
                $datasource[$i] = "";
            }
            array_push($final, $datasource[$i]);
        }
        return json_encode($final);
    }

    /**
     * @param $abandonedCartData
     * @param $sentMailsData
     * @param $convertedData
     * @return array
     */
    public function prepareDataSet($abandonedCartData, $sentMailsData, $convertedData)
    {
        return $data = "[{
                label: '".__('Abandoned Cart')."',
                backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
                borderColor: window.chartColors.red,
                borderWidth: 1,
                data: $abandonedCartData
            }, {
                label: '".__('Mails Sent')."',
                backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
                borderColor: window.chartColors.blue,
                borderWidth: 1,
                data: $sentMailsData
            }, {
                label: '".__('Carts Recovered')."',
                backgroundColor: color(window.chartColors.green).alpha(0.5).rgbString(),
                borderColor: window.chartColors.blue,
                borderWidth: 1,
                data: $convertedData
            }
        ]";
    }
}
