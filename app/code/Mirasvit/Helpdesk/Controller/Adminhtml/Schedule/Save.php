<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Controller\Adminhtml\Schedule;

use Mirasvit\Helpdesk\Api\Data\ScheduleInterface;

class Save extends \Mirasvit\Helpdesk\Controller\Adminhtml\Schedule
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    public function __construct(
        \Mirasvit\Helpdesk\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->serializer = $serializer;

        parent::__construct($scheduleFactory, $localeDate, $registry, $context);
    }

    /**
     *
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            $schedule = $this->_initSchedule();

            $data['working_hours'] = $this->prepareSchedule($data);
            if (!$data['working_hours']) {
                $this->messageManager->addErrorMessage(__('Working days/hours is a required field.'));
                $this->backendSession->setFormData($data);
                return $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
            unset($data['working_time'], $data['working_time_minute'], $data['working_time_day']);

            $schedule->addData($this->prepareData($data));

            try {
                $schedule->save();

                $this->messageManager->addSuccessMessage(__('Schedule was successfully saved'));
                $this->backendSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect(
                        '*/*/edit', ['id' => $schedule->getId(), 'store' => $schedule->getStoreId()]
                    );
                }
                return $this->_redirect('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->backendSession->setFormData($data);
                return $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find a Schedule to save'));
        return $this->_redirect('*/*/');
    }

    /**
     * @param array $data
     * @return bool|string
     */
    protected function prepareSchedule($data)
    {
        $schedule = [];
        if ($data['type'] == \Mirasvit\Helpdesk\Model\Config::SCHEDULE_TYPE_CUSTOM) {
            if (empty($data['working_time_day'])) {
                return false;
            }
            foreach ($data['working_time_day'] as $day => $value) {
                if ($value == 1) {
                    $schedule[$day] = [
                        'from' => $data['working_time'][$day]['time_from'][0],
                        'to' => $data['working_time'][$day]['time_to'][0],
                    ];
                }
            }
        }
        return $this->serializer->serialize($schedule);
    }

    /**
     * @param array $data
     * @return array
     */
    public function prepareData($data)
    {
        if (empty($data[ScheduleInterface::ID])) {
            unset($data[ScheduleInterface::ID]);
            unset($data['id']);
        }

        return $data;
    }
}
