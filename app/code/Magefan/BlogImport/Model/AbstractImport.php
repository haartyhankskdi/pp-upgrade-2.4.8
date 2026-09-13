<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

use Magefan\Blog\Model\CategoryFactory;
use Magefan\Blog\Model\CommentFactory;
use Magefan\Blog\Model\PostFactory;
use Magefan\Blog\Model\TagFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Abstract import model
 */
abstract class AbstractImport extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var mixed
     */
    protected $_connect;

    /**
     * @var array
     */
    protected $_requiredFields = [];

    /**
     * @var \Magefan\Blog\Model\PostFactory
     */
    protected $_postFactory;

    /**
     * @var \Magefan\Blog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magefan\Blog\Model\TagFactory
     */
    protected $_tagFactory;

    /**
     * @var \Magefan\Blog\Model\CommentFactory
     */
    protected $_commentFactory;

    /**
     * @var integer
     */
    protected $_importedPostsCount = 0;

    /**
     * @var integer
     */
    protected $_importedCategoriesCount = 0;

    /**
     * @var integer
     */
    protected $_importedTagsCount = 0;

    /**
     * @var integer
     */
    protected $_importedCommentsCount = 0;

    /**
     * @var integer
     */
    protected $_importedAuthorsCount = 0;

    /**
     * @var array
     */
    protected $_skippedPosts = [];

    /**
     * @var array
     */
    protected $_skippedCategories = [];

    /**
     * @var array
     */
    protected $_skippedTags = [];

    /**
     * @var array
     */
    protected $_skippedComments = [];

    /**
     * @var array
     */
    protected $_skippedAuthors = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magefan\Blog\Api\AuthorInterfaceFactory|mixed
     */
    protected $_authorFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository|mixed
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * AbstractImport constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param PostFactory $postFactory
     * @param CategoryFactory $categoryFactory
     * @param TagFactory $tagFactory
     * @param CommentFactory $commentFactory
     * @param StoreManagerInterface $storeManager
     * @param ConnectionFactory $connectionFactory
     * @param Filesystem $filesystem
     * @param File $file
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param mixed $authorFactory
     * @param mixed $productRepository
     * @throws LocalizedException
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magefan\Blog\Model\PostFactory $postFactory,
        \Magefan\Blog\Model\CategoryFactory $categoryFactory,
        \Magefan\Blog\Model\TagFactory $tagFactory,
        \Magefan\Blog\Model\CommentFactory $commentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory $connectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        $authorFactory = null,
        $productRepository = null
    ) {
        $this->_postFactory = $postFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_tagFactory = $tagFactory;
        $this->_commentFactory = $commentFactory;
        $this->_storeManager = $storeManager;
        $this->connectionFactory = $connectionFactory;
        $this->fileSystem = $filesystem;
        $this->file = $file;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_authorFactory = $authorFactory ?: $objectManager
            ->get(\Magefan\Blog\Api\AuthorInterfaceFactory::class);
        $this->productRepository = $productRepository ?: $objectManager
            ->get(\Magento\Catalog\Model\ProductRepository::class);

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve import statistic
     *
     * @return \Magento\Framework\DataObject
     */
    public function getImportStatistic()
    {
        return new \Magento\Framework\DataObject([
            'imported_posts_count'      => $this->_importedPostsCount,
            'imported_categories_count' => $this->_importedCategoriesCount,
            'imported_tags_count'       => $this->_importedTagsCount,
            'imported_comments_count'   => $this->_importedCommentsCount,
            'imported_authors_count'   => $this->_importedAuthorsCount,
            'imported_count'            => $this->_importedPostsCount +
                $this->_importedCategoriesCount +
                $this->_importedTagsCount +
                $this->_importedCommentsCount,
            $this->_importedAuthorsCount,

            'skipped_posts'             => $this->_skippedPosts,
            'skipped_categories'        => $this->_skippedCategories,
            'skipped_tags'              => $this->_skippedTags,
            'skipped_comments'          => $this->_skippedComments,
            'skipped_authors'          => $this->_skippedAuthors,
            'skipped_count'             => count($this->_skippedPosts) +
                count($this->_skippedCategories) +
                count($this->_skippedTags) +
                count($this->_skippedComments),
            count($this->_skippedAuthors),
        ]);
    }

    /**
     * Prepare import data
     *
     * @param  array $data
     * @return $this
     * @throws \Exception
     */
    public function prepareData($data)
    {
        if (!is_array($data)) {
            $data = (array) $data;
        }

        foreach ($this->_requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception(__('Parameter %1 is required', $field), 1);
            }
        }

        $this->setData($data);

        return $this;
    }

    /**
     * Prepare import identifier
     *
     * @param  string $identifier
     * @return string
     */
    protected function prepareIdentifier($identifier)
    {
        $identifier = urldecode(trim(strtolower($identifier)));

        if (is_numeric($identifier)) {
            $identifier .= 'u' . $identifier;
        }

        if (strlen($identifier) == 1) {
            $identifier .= $identifier;
        }

        return $identifier;
    }

    /**
     * Get table prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        $connection = $this->getDbConnection();

        $_pref = '';

        if ($this->getData('prefix')) {
            $_pref = $connection->quote($this->getData('prefix'));
            $_pref = trim($_pref, "'");
        }

        return $_pref;
    }

    /**
     * Get DB connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getDbConnection()
    {
        $connectionConf = [
            'driver' => 'Pdo_Mysql',
            'dbname' => $this->getData('dbname'),
            'username' => $this->getData('uname'),
            'password' => $this->getData('pwd'),
            'charset' => 'utf8',
        ];

        if ($this->getData('dbhost')) {
            $connectionConf['host'] = $this->getData('dbhost');
        }

        return $this->connectionFactory->create($connectionConf);
    }

    /**
     * Get featured image by src
     *
     * @param string $src
     * @return false|string
     */
    protected function getFeaturedImgBySrc($src)
    {
        $imageName = explode('?', $src);
        $imageName = explode('#', $src);
        $imageName = explode('/', $imageName[0]);
        $imageName = end($imageName);
        $imageName = str_replace(['%20', ' '], '-', $imageName);
        $imageName = urldecode($imageName);

        $hasFormat = false;
        foreach (['jpg','jpeg', 'png', 'gif', 'webp'] as $format) {
            if (false !== stripos($imageName, $format)) {
                $hasFormat = true;
                break;
            }
        }
        if (!$hasFormat) {
            $imageName .= '.jpg';
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $formatIdentifier = $objectManager->get(\Magefan\Blog\Model\ResourceModel\PageIdentifierGenerator::class);
        $imageNameWithoutFormat = explode('.', $imageName);
        $preparedImageName = $formatIdentifier->formatIdentifier($imageNameWithoutFormat[0]);
        $imageName = $preparedImageName . '.' . $imageNameWithoutFormat[1];

        $mediaDirectory = $this->fileSystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
        $imageSource = false;
        $relativePath = 'magefan_blog/' . $imageName;
        if (!$mediaDirectory->isExist($relativePath)) {
            try {
                $imageData = $this->file->read($src);
                if ($imageData !== false) {
                    $mediaDirectory->getDriver()->filePutContents(
                        $mediaDirectory->getAbsolutePath($relativePath),
                        $imageData
                    );
                    $imageSource = true;
                }
            } catch (\Throwable $e) {
                $imageSource = false;
            }
        } else {
            $imageSource = true;
        }

        if ($imageSource) {
            return 'magefan_blog/' . $imageName;
        } else {
            return false;
        }
    }

    /**
     * Parse content
     *
     * @param string $content
     * @return array|string|string[]|null
     */
    protected function parseContent(string $content)
    {
        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fileSystem = $objectManager->create(\Magento\Framework\Filesystem::class);
        $mediaPath = $fileSystem
                ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
                ->getAbsolutePath() . '/wysiwyg/blog';
        $this->file->mkdir($mediaPath, 0775);

        foreach (['/src="(.*)"/Ui', '/src=\'(.*)\'/Ui'] as $patern) {

            $matches = [];
            preg_match_all($patern, $content, $matches);

            if (!empty($matches[1])) {

                foreach ($matches[1] as $src) {
                    //$src = $matches[1];
                    $imageName = explode('?', $src);
                    $imageName = explode('#', $src);
                    $imageName = explode('/', $imageName[0]);
                    $imageName = end($imageName);
                    $imageName = str_replace(['%20', ' '], '-', $imageName);
                    $imageName = urldecode($imageName);
                    $imagePath = $mediaPath . '/' . $imageName;
					// phpcs:disable Magento2.Functions.DiscouragedFunction
                    if (!file_exists($imagePath)) {

                        if ($imageSource = file_get_contents($src)) {
                            file_put_contents(
                                $imagePath,
                                $imageSource
                            );
                        }
                    } else {
                        $imageSource = true;
                    }
					// phpcs:enable Magento2.Functions.DiscouragedFunction
                    if ($imageSource) {
                        $content = str_replace($src, '{{media url=\'wysiwyg/blog/' . $imageName . '\'}}', $content);
                    } else {
                        $pixel =
                            'data:image/png;base64,'
                            . 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk'
                            . 'YPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

                        $content = str_replace($src, $pixel, $content);
                    }
                }
            }

        }

        $content = preg_replace(
            '/(srcset=".*")/Ui',
            's',
            $content
        );
        $content = preg_replace(
            '/(srcset=\'.*\')/Ui',
            's',
            $content
        );

        return $content;
    }

    /**
     * Check connection
     *
     * @param mixed $connection
     * @param string $table
     * @param string $onFailureMessage
     * @return void
     * @throws \Exception
     */
    protected function checkConnection($connection, string $table, string $onFailureMessage): void
    {
        $select = $connection->select()->from($table)->limit(1);

        try {
            $connection->fetchAll($select);
        } catch (\Exception $e) {
            throw new \Exception((string)__($onFailureMessage), 1);
        }
    }

    /**
     * Get DB connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);

        return $resourceConnection->getConnection();
    }
}
