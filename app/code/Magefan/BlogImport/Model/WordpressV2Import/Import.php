<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\BlogImport\Api\WpImportManagementInterface;
use Magefan\BlogImport\Model\Key;
use Magefan\BlogImport\Model\WordpressV2Import\Author as ImportAuthor;
use Magefan\BlogImport\Model\WordpressV2Import\Category as ImportCategory;
use Magefan\BlogImport\Model\WordpressV2Import\Comment as ImportComment;
use Magefan\BlogImport\Model\WordpressV2Import\Post as ImportPost;
use Magefan\BlogImport\Model\WordpressV2Import\Tag as ImportTag;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Import implements WpImportManagementInterface
{
    public const CATEGORY = 'category';
    public const TAG = 'tag';
    public const AUTHOR = 'author';
    public const POST = 'post';
    public const COMMENT = 'comment';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Key
     */
    private $importKey;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Category
     */
    private $categoryImport;

    /**
     * @var Tag
     */
    private $tagImport;

    /**
     * @var Post
     */
    private $postImport;

    /**
     * @var Comment
     */
    private $commentImport;

    /**
     * @var Author
     */
    private $authorImport;

    /**
     * @param JsonFactory $jsonFactory
     * @param Category $categoryImport
     * @param Tag $tagImport
     * @param Post $postImport
     * @param Comment $commentImport
     * @param Author $authorImport
     * @param Key $importKey
     * @param ScopeConfigInterface $scopeConfig
     * @param AdminSession $adminSession
     * @param RequestInterface $request
     */
    public function __construct(
        JsonFactory          $jsonFactory,
        ImportCategory       $categoryImport,
        ImportTag            $tagImport,
        ImportPost           $postImport,
        ImportComment        $commentImport,
        ImportAuthor         $authorImport,
        Key                  $importKey,
        ScopeConfigInterface $scopeConfig,
        AdminSession         $adminSession,
        RequestInterface     $request
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->categoryImport = $categoryImport;
        $this->tagImport = $tagImport;
        $this->postImport = $postImport;
        $this->commentImport = $commentImport;
        $this->authorImport = $authorImport;
        $this->importKey = $importKey;
        $this->_scopeConfig = $scopeConfig;
        $this->adminSession = $adminSession;
        $this->request = $request;
    }

    /**
     * Imports data based on the specified entity type and returns the result as a JSON-encoded string.
     *
     * @return string JSON-encoded response containing imported entity IDs or an error message
     */
    public function wpImport()
    {
        $requestData = $this->request->getContent();
        $requestData = json_decode($requestData, true);

        if (!isset($requestData['data']) || !isset($requestData['entity'])) {
            return json_encode(['errorMessage' => 'Invalid data or entity']);
        }

        $data = $requestData['data'];
        $isValid = $this->importKey->validate($data[0]['importKey'] ?? null);

        if (!$isValid) {
            return json_encode(['errorMessage' => 'Wrong import key']);
        }

        $ids = [];

        switch ($requestData['entity']) {
            case self::CATEGORY:
                $ids = $this->categoryImport->execute($data);
                break;
            case self::TAG:
                $ids = $this->tagImport->execute($data);
                break;
            case self::AUTHOR:
                $ids = $this->authorImport->execute($data);
                break;
            case self::POST:
                $ids = $this->postImport->execute($data);
                break;
            case self::COMMENT:
                 $ids = $this->commentImport->execute($data);
                break;
        }

        return json_encode([$requestData['entity'] . 'Ids' => implode(',', $ids)]);
    }
}
