<?php

/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Haartyhanks\AuthReview\ViewModel;

use Magefan\Blog\Model\PostFactory;
use Magento\Framework\App\Request\Http;
use Haartyhanks\AuthReview\Model\EntityFactory;


class Post extends \Magento\Framework\DataObject implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    protected $postModel;
    protected $http;
    protected $authModel;

    /**
     * Post constructor.
     *
     */
    public function __construct(
        PostFactory $postFactory,
        Http $http,
        EntityFactory $entityFactory
    ) {
        $this->postModel = $postFactory;
        $this->http = $http;
        $this->authModel = $entityFactory;
        parent::__construct();
    }


    public function GetPost()
    {
        $id = $this->getId();

        $post = $this->postModel->create()->load($id);
        
        // print_r($post->getData());
        if ($post['is_active_google_eeat']) {
            return [
                'status' => $post['is_active_google_eeat'],
                'review' => $post['post_review_id'],
                'author' => $post['post_author_id'],
            ];
        }

        return [];
    }

    public function getId()
    {
        $params = $this->http->getParams();
        $postId =  $params['id'];
        if ($postId) {
            return $postId;
        }
        return null;
    }

    public function getTeamInfo($id)
    {
        $data =  $this->authModel->create()->load($id);
        if ($data) {
            return $data;
        }
        return null;
    }
}
