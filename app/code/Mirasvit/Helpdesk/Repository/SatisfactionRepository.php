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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Repository;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Mirasvit\Helpdesk\Model\Message;
use Mirasvit\Helpdesk\Model\Satisfaction;
use Mirasvit\Helpdesk\Model\SatisfactionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction as SatisfactionResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\CollectionFactory;
use Mirasvit\Helpdesk\Model\Ticket;
use Mirasvit\Helpdesk\Api\Data\SatisfactionSearchResultsInterfaceFactory;

class SatisfactionRepository
{
    use \Mirasvit\Helpdesk\Repository\RepositoryFunction\GetList;

    /**
     * @var Satisfaction[]
     */
    private $instances = [];

    private $objectFactory;

    private $satisfactionCollectionFactory;

    private $satisfactionResource;

    private $searchResultsFactory;

    public function __construct(
        SatisfactionFactory $satisfactionFactory,
        SatisfactionResource $satisfactionResource,
        CollectionFactory $satisfactionCollectionFactory,
        SatisfactionSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->objectFactory        = $satisfactionFactory;
        $this->satisfactionResource = $satisfactionResource;
        $this->searchResultsFactory = $searchResultsFactory;

        $this->satisfactionCollectionFactory = $satisfactionCollectionFactory;
    }

    /**
     * @param Ticket $ticket
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save($ticket)
    {
        $this->satisfactionResource->save($ticket);

        return $ticket;
    }

    /**
     * @param int $id
     * @return Satisfaction
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var Satisfaction $ticket */
            $ticket = $this->objectFactory->create()->load($id);
            if (!$ticket->getId()) {
                throw NoSuchEntityException::singleField('id', $id);
            }
            $this->instances[$id] = $ticket;
        }

        return $this->instances[$id];
    }

    /**
     * @param Satisfaction $satisfaction
     * @return bool
     * @throws StateException
     */
    public function delete($satisfaction)
    {
        try {
            $id = $satisfaction->getId();

            $this->satisfactionResource->delete($satisfaction);
        } catch (\Exception $e) {
            throw new StateException(
                __(
                    'Cannot delete template with id %1',
                    $satisfaction->getId()
                ),
                $e
            );
        }

        unset($this->instances[$id]);

        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($id)
    {
        $satisfaction = $this->get($id);

        return  $this->delete($satisfaction);
    }

    /**
     * @param Message $message
     *
     * @return Satisfaction
     */
    public function getByMessage($message)
    {
        $satisfactions = $this->satisfactionCollectionFactory->create()
            ->addFieldToFilter('message_id', $message->getId());

        if ($satisfactions->count()) {
            return $satisfactions->getFirstItem();
        } else {
            return null;
        }
    }
}
