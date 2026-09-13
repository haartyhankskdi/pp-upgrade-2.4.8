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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Report\Repository;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Mirasvit\Report\Api\Data\Email\BlockInterface;
use Mirasvit\Report\Api\Data\EmailInterface;
use Mirasvit\Report\Api\Data\EmailSearchResultsInterface;
use Mirasvit\Report\Model\EmailSearchResultsFactory as EmailSearchResultsInterfaceFactory;
use Mirasvit\Report\Api\Repository\Email\BlockRepositoryInterface;
use Mirasvit\Report\Api\Repository\EmailRepositoryInterface;
use Mirasvit\Report\Api\Service\DateServiceInterface;
use Mirasvit\Report\Model\EmailFactory;
use Mirasvit\Report\Model\ResourceModel\Email\CollectionFactory as CollectionFactory;

class EmailRepository implements EmailRepositoryInterface
{
    /**
     * @var EmailFactory
     */
    private $factory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $repositoryPool = [];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var EmailSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var DateServiceInterface
     */
    private $dateService;

    /**
     * @param EmailFactory $emailFactory
     * @param CollectionFactory $collectionFactory
     * @param EntityManager $entityManager
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param CollectionProcessorInterface $collectionProcessor
     * @param EmailSearchResultsInterfaceFactory $searchResultsFactory
     * @param array $repositoryPool
     */
    public function __construct(
        EmailFactory                                   $emailFactory,
        CollectionFactory                              $collectionFactory,
        EntityManager                                  $entityManager,
        ObjectManagerInterface                         $objectManager,
        \Magento\Framework\Serialize\Serializer\Json   $serializer,
        CollectionProcessorInterface                   $collectionProcessor,
        EmailSearchResultsInterfaceFactory             $searchResultsFactory,
        DateServiceInterface                           $dateService,
        array                                          $repositoryPool = []
    ) {
        $this->factory              = $emailFactory;
        $this->collectionFactory    = $collectionFactory;
        $this->entityManager        = $entityManager;
        $this->objectManager        = $objectManager;
        $this->serializer           = $serializer;
        $this->collectionProcessor  = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dateService          = $dateService;
        $this->repositoryPool       = $repositoryPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return $this->factory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $model = $this->create();

        $this->entityManager->load($model, (string)$id);

        return $model->getId() ? $model : null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(EmailInterface $email)
    {
        $this->entityManager->delete($email);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save(EmailInterface $email)
    {
        /** @var \Mirasvit\Report\Model\Email $email */
        $blocks = $email->getData(EmailInterface::BLOCKS);

        if (is_array($blocks)) {
            $validIdentifiers = array_column($this->getReports(), 'value');
            $validTimeRanges  = array_keys($this->dateService->getIntervals());

            $serializable = [];
            foreach ($blocks as $block) {
                $identifier = $block instanceof BlockInterface
                    ? $block->getIdentifier()
                    : ($block['identifier'] ?? '');

                $timeRange = $block instanceof BlockInterface
                    ? $block->getTimeRange()
                    : ($block['timeRange'] ?? $block['time_range'] ?? '');

                if ($identifier && !in_array((string)$identifier, $validIdentifiers)) {
                    throw new LocalizedException(__(
                        'Block identifier "%1" does not match any available report or dashboard block. Use GET /V1/reports/reports to discover valid identifiers.',
                        $identifier
                    ));
                }

                if ($timeRange && !in_array((string)$timeRange, $validTimeRanges)) {
                    throw new LocalizedException(__(
                        'Time range "%1" is not valid. Valid values: %2',
                        $timeRange,
                        implode(', ', $validTimeRanges)
                    ));
                }

                if ($block instanceof BlockInterface) {
                    $serializable[] = [
                        'identifier' => $block->getIdentifier(),
                        'timeRange'  => $block->getTimeRange(),
                        'limit'      => $block->getLimit(),
                    ];
                } elseif (is_array($block)) {
                    $serializable[] = $block;
                }
            }
            $email->setData(EmailInterface::BLOCKS_SERIALIZED, $this->serializer->serialize($serializable));
        }

        $this->entityManager->save($email);

        return $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getReports()
    {
        $reports = [];

        foreach ($this->repositoryPool as $repositoryClass) {
            /** @var BlockRepositoryInterface $repository */
            $repository = $this->objectManager->get($repositoryClass);

            foreach ($repository->getBlocks() as $identifier => $block) {
                $reports[] = [
                    'value'      => $identifier,
                    'label'      => $block,
                    'repository' => $repository,
                ];
            }
        }

        return $reports;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $emailId): EmailInterface
    {
        $model = $this->get($emailId);

        if (!$model) {
            throw new NoSuchEntityException(__('Email with ID "%1" does not exist.', $emailId));
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): EmailSearchResultsInterface
    {
        $collection = $this->getCollection();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var EmailSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \Mirasvit\Report\Api\Data\EmailInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function saveEmail(EmailInterface $email, ?int $emailId = null): EmailInterface
    {
        if ($emailId) {
            $model = $this->getById($emailId);

            if ($email instanceof DataObject) {
                if ($email->hasData(EmailInterface::TITLE)) {
                    $model->setTitle($email->getTitle());
                }
                if ($email->hasData(EmailInterface::SUBJECT)) {
                    $model->setSubject($email->getSubject());
                }
                if ($email->hasData(EmailInterface::RECIPIENT)) {
                    $model->setRecipient($email->getRecipient());
                }
                if ($email->hasData(EmailInterface::SCHEDULE)) {
                    $model->setSchedule($email->getSchedule());
                }
                if ($email->hasData(EmailInterface::IS_ACTIVE)) {
                    $model->setIsActive($email->getIsActive());
                }
                if ($email->hasData(EmailInterface::IS_ATTACH_ENABLED)) {
                    $model->setIsAttachEnabled($email->getIsAttachEnabled());
                }
                if ($email->hasData(EmailInterface::BLOCKS)) {
                    $model->setBlocks($email->getBlocks());
                }
            } else {
                if ($email->getTitle()) {
                    $model->setTitle($email->getTitle());
                }
                if ($email->getSubject()) {
                    $model->setSubject($email->getSubject());
                }
                if ($email->getRecipient()) {
                    $model->setRecipient($email->getRecipient());
                }
                if ($email->getSchedule()) {
                    $model->setSchedule($email->getSchedule());
                }
                $model->setIsActive($email->getIsActive());
                $model->setIsAttachEnabled($email->getIsAttachEnabled());
                $blocks = $email->getBlocks();
                if (!empty($blocks)) {
                    $model->setBlocks($blocks);
                }
            }
        } else {
            $model = $this->create();
            $model->setTitle($email->getTitle());
            $model->setSubject($email->getSubject());
            $model->setRecipient($email->getRecipient());
            $model->setSchedule($email->getSchedule());
            $model->setIsActive($email->getIsActive());
            $model->setIsAttachEnabled($email->getIsAttachEnabled());
            $model->setBlocks($email->getBlocks());
        }

        $this->save($model);

        return $this->getById((int)$model->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $emailId): bool
    {
        $model = $this->getById($emailId);

        return $this->delete($model);
    }
}
