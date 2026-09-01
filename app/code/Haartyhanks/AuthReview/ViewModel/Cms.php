<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\AuthReview\ViewModel;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\RequestInterface;
use Haartyhanks\AuthReview\Model\EntityFactory;

class Cms extends \Magento\Framework\DataObject implements ArgumentInterface
{

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var RequestInterface
     */
    protected $request;

    protected $model;

    public function __construct(
        Page $page,
        RequestInterface $request,
        EntityFactory $entityFactory
    ) {
        $this->page    = $page;
        $this->request = $request;
        $this->model = $entityFactory;
    }

    public function getPageId(): ?int
    {
        if ($this->page->getId()) {
            return (int)$this->page->getId();
        }

        return null;
    }

    public function getStatus(){
        $pageId = $this->getPageId();
        if($pageId){
            $pageData = $this->page->load($pageId);
            if ($pageData->getData('is_page_review_enable')) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function getReview(){
        $pageId = $this->getPageId();
        if($pageId){
            $pageData = $this->page->load($pageId);
            if ($pageData->getData('page_review_by')) {
                $review = $this->model->create()->load($pageData->getData('page_review_by'));
                return [
                    'name' => $review->getData('name'),
                    'photo' => $review->getData('photo'),
                    'registration_number' => $review->getData('registration_number'),
                    'specialist' => $review->getData('specialist'),
                ];
            }
            return false;
        }
        return false;
    }

    public function getAuthor(){
        $pageId = $this->getPageId();
        if($pageId){
            $pageData = $this->page->load($pageId);
            if ($pageData->getData('page_author_by')) {
                $review = $this->model->create()->load($pageData->getData('page_author_by'));
                return [
                    'name' => $review->getData('name'),
                    'photo' => $review->getData('photo'),
                    'registration_number' => $review->getData('registration_number'),
                    'specialist' => $review->getData('specialist'),
                ];
            }
            return false;
        }
        return false;
    }
    
}
