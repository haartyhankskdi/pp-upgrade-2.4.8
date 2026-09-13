<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Customform\Api\Data;
use Amasty\Customform\Model\Answer\FinderInterfaceFactory as AnswersFinderFactory;
use Amasty\Customform\Model\ResourceModel\Answer as AnswerResource;
use Amasty\Customform\Model\ResourceModel\Answer\CollectionFactory as AnswerCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AnswerRepository implements \Amasty\Customform\Api\AnswerRepositoryInterface
{
    /**
     * @var array
     */
    protected $answer = [];

    /**
     * @var ResourceModel\Answer
     */
    private $answerResource;

    /**
     * @var AnswerFactory
     */
    private $answerFactory;

    /**
     * @var ResourceModel\Answer\CollectionFactory
     */
    private $answerCollectionFactory;

    /**
     * @var AnswersFinderFactory
     */
    private $answersFinderFactory;

    public function __construct(
        AnswerResource $answerResource,
        AnswerFactory $answerFactory,
        AnswerCollectionFactory $answerCollectionFactory,
        AnswersFinderFactory $answersFinderFactory
    ) {
        $this->answerResource = $answerResource;
        $this->answerFactory = $answerFactory;
        $this->answerCollectionFactory = $answerCollectionFactory;
        $this->answersFinderFactory = $answersFinderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Data\AnswerInterface $answer)
    {
        if ($answer->getAnswerId()) {
            $answer = $this->get($answer->getAnswerId())->addData($answer->getData());
        }

        try {
            $this->answerResource->save($answer);
            unset($this->answer[$answer->getAnswerId()]);
        } catch (\Exception $e) {
            if ($answer->getAnswerId()) {
                throw new CouldNotSaveException(
                    __('Unable to save answer with ID %1. Error: %2', [$answer->getAnswerId(), $e->getMessage()])
                );
            }
            throw new CouldNotSaveException(__('Unable to save new answer. Error: %1', $e->getMessage()));
        }

        return $answer;
    }

    /**
     * {@inheritdoc}
     */
    public function get($answerId)
    {
        if (!isset($this->answer[$answerId])) {
            /** @var \Amasty\Customform\Model\Answer $answer */
            $answer = $this->answerFactory->create();
            $this->answerResource->load($answer, $answerId);
            if (!$answer->getAnswerId()) {
                throw new NoSuchEntityException(__('Answer with specified ID "%1" was not found.', $answerId));
            }
            $this->answer[$answerId] = $answer;
        }
        return $this->answer[$answerId];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Data\AnswerInterface $answer)
    {
        try {
            $this->answerResource->delete($answer);
            unset($this->answer[$answer->getAnswerId()]);
        } catch (\Exception $e) {
            if ($answer->getAnswerId()) {
                throw new CouldNotDeleteException(
                    __('Unable to remove answer with ID %1. Error: %2', [$answer->getAnswerId(), $e->getMessage()])
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove answer. Error: %1', $e->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($answerId)
    {
        $model = $this->get($answerId);
        $this->delete($model);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getListFilter(SearchCriteriaInterface $searchCriteria)
    {
        $finder = $this->answersFinderFactory->create();
        $finder->setSearchCriteria($searchCriteria);
        $answerList = [];

        foreach ($finder->getResult() as $answer) {
            $answerList[] = $answer;
        }

        return $answerList;
    }

    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        $answerCollection = $this->answerCollectionFactory->create();
        $answerList = [];

        foreach ($answerCollection as $answer) {
            $answerList[] = $answer;
        }

        return $answerList;
    }

    public function _resetState(): void
    {
        $this->answer = [];
    }
}
