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



namespace Mirasvit\Helpdesk\Ui\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Mirasvit\Helpdesk\Api\Data\ActivityInterface;
use Mirasvit\Helpdesk\Repository\ActivityRepository;
use Mirasvit\Helpdesk\Model\ResourceModel\Department\Collection as DepartmentCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Activity extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private $activityRepository;

    private $departmentCollection;

    private $userCollection;

    private $request;

    private $timezone;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RequestInterface $request,
        ActivityRepository $activityRepository,
        DepartmentCollection $departmentCollection,
        UserCollection $userCollection,
        TimezoneInterface $timezone,
        array $meta = [],
        array $data = []
    ) {
        $this->request              = $request;
        $this->activityRepository   = $activityRepository;
        $this->departmentCollection = $departmentCollection;
        $this->userCollection       = $userCollection;
        $this->timezone             = $timezone;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getConfigData(): array
    {
        $data = parent::getConfigData();

        $data['day'] = rand(-100, 0);

        return $data;
    }

    public function getData(): array
    {
        $departmentCollection = $this->departmentCollection->load();
        $departmentUserIds = [];
        foreach ($departmentCollection as $department) {
            foreach ($department->getUsers() as $user) {
                if (!in_array($user->getId(), $departmentUserIds)) {
                    $departmentUserIds[] = $user->getId();
                }
            }
        }

        $userCollection = $this->userCollection;
        $userCollection->addFilterToMap('user_id', 'main_table.user_id');
        $userCollection
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('user_id', ['in'=> $departmentUserIds])
            ->setOrder('username', 'asc');

        $users  = [];
        $groups = [];
        /** @var \Magento\User\Model\User $user */
        foreach ($userCollection as $user) {
            $users[$user->getId()] = $user->getUserName();

            $groups[$user->getId()] = [
                'name'     => $user->getUserName(),
                'identity' => $user->getId(),
                'points'   => [],
            ];
        }

        $offset = $this->request->getParam('offset', 0);

        $from = date('Y-m-d 00:00:00', (int) strtotime("$offset days", time()));
        $to   = date('Y-m-d 23:59:59', (int) strtotime("$offset days", time()));

        $activity = $this->activityRepository->getCollection();
        $activity->addFieldToFilter(ActivityInterface::USER_ID, ['in' => array_keys($users)]);
        $activity->addFieldToFilter(ActivityInterface::TIMESTAMP, ['gteq' => $from])
            ->addFieldToFilter(ActivityInterface::TIMESTAMP, ['lteq' => $to]);

        /** @var ActivityInterface $act */
        foreach ($activity as $act) {
            $timeDiff = ((new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(
                (new \DateTime($act->getTimestamp(),
                    (new \DateTimeZone('UTC'))))));

            $time = strtotime($act->getTimestamp()) - strtotime($from) + $timeDiff;

            $groups[$act->getUserId()]['points'][] = [
                'id'          => $act->getId(),
                'title'       => $act->getTitle(),
                'description' => $act->getDescription(),
                'url'         => $act->getUrl(),
                'time'        => $time,
                'timestamp'   => date($this->timezone->formatDateTime($act->getTimestamp())),
                'kind'        => $act->getKind(),
            ];
        }

        $prev         = $offset - 1;
        $prevLabel    = date('M, d', (int) strtotime("$prev days", time()));
        $currentLabel = date('M, d', (int) strtotime("$offset days", time()));
        $next         = $offset + 1;
        $nextLabel    = date('M, d', (int) strtotime("$next days", time()));

        return [
            'prev'         => $prev,
            'prevLabel'    => '⇦ ' . $prevLabel,
            'currentLabel' => $currentLabel,
            'nextLabel'    => $nextLabel . ' ⇨',
            'next'         => $next,
            'totalRecords' => count($groups),
            'items'        => array_values($groups),
        ];
    }
}
