<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

// phpcs:disable Magento2.Functions.DiscouragedFunction
use Magento\Framework\Exception\LocalizedException;

/**
 * Magefan Blog Helper
 */
class Csv extends \Magefan\BlogImport\Model\AbstractImport
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Csv constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magefan\Blog\Model\PostFactory $postFactory
     * @param \Magefan\Blog\Model\CategoryFactory $categoryFactory
     * @param \Magefan\Blog\Model\TagFactory $tagFactory
     * @param \Magefan\Blog\Model\CommentFactory $commentFactory
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory $connectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magefan\Blog\Model\PostFactory $postFactory,
        \Magefan\Blog\Model\CategoryFactory $categoryFactory,
        \Magefan\Blog\Model\TagFactory $tagFactory,
        \Magefan\Blog\Model\CommentFactory $commentFactory,
        \Magento\Backend\Model\Session $session,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory $connectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $postFactory,
            $categoryFactory,
            $tagFactory,
            $commentFactory,
            $storeManager,
            $connectionFactory,
            $filesystem,
            $file,
            $resource,
            $resourceCollection,
            $data
        );

        $this->session = $session;
        $this->directoryList = $directoryList;
        $this->messageManager = $messageManager;
    }

    /**
     * Retrieves rows from a CSV or XML file.
     *
     * @param int|null $rowsCount
     * @return array
     * @throws LocalizedException
     */
    public function getRows($rowsCount = null)
    {
        $file = $this->session->getData('import_csv_file');
        if (!$file) {
            throw new LocalizedException("CSV is missing.", 1);
        }

        $mediaDirectory = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        if (!$mediaDirectory->isExist($file)) {
            throw new \LocalizedException("CSV file no longer exists.", 1);
        }

        $localFile = $mediaDirectory->getAbsolutePath($file);

        if (!file_exists($localFile)) {
            $content = $mediaDirectory->readFile($file);
            $tmpDirectory = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::TMP);
            $tmpFileName = basename($file);
            $tmpDirectory->writeFile($tmpFileName, $content);
            $localFile = $tmpDirectory->getAbsolutePath($tmpFileName);
        }

        $fileFormat = explode('.', $localFile);
        $fileFormat = end($fileFormat);
        return (stripos($fileFormat, 'csv') !== false)
            ? $this->getCsvRows($localFile, $rowsCount)
            : $this->getXMLRows($localFile, $rowsCount);
    }

    /**
     * Reads rows from a CSV file and returns an array of rows.
     *
     * @param string $file
     * @param int|null $rowsCount
     * @return array
     * @throws LocalizedException
     */
    protected function getCsvRows($file, $rowsCount = null)
    {
        $delimeter = $this->getDelimeter($file);

        if (($handle = fopen($file, "r")) !== false) {
            $data = [];
            $rowBuffer = '';

            while (($line = fgets($handle)) !== false) {
                $rowBuffer .= $line;

                if (substr_count($rowBuffer, '"') % 2 === 0) {
                    preg_match_all(
                        '/(?:^|' . preg_quote($delimeter, '/') . ')(?:"([^"]*(?:""[^"]*)*)"|([^'
                        . preg_quote($delimeter, '/')
                        . ']*))/',
                        $rowBuffer,
                        $matches
                    );

                    $parsedRow = [];
                    foreach ($matches[1] as $k => $v) {
                        $parsedRow[] = $v !== '' ? str_replace('""', '"', $v) : $matches[2][$k];
                    }

                    $data[] = $parsedRow;
                    $rowBuffer = '';
                }
            }

            fclose($handle);
            return $data;
        } else {
            throw new LocalizedException("Cannot read csv file.", 1);
        }
    }

    /**
     * Reads rows from an XML file and returns an array of rows.
     *
     * @param string $file
     * @param int|null $rowsCount
     * @return array
     * @throws LocalizedException
     */
	// phpcs:disable Generic.Metrics.NestingLevel
    protected function getXMLRows($file, $rowsCount = null)
    {
        if (($xml = simplexml_load_file($file)) !== false) {
            $data = [];
            $i = 0;
            $items = $xml->channel ? $xml->channel->item : ($xml->item ?: $xml->entry);
            foreach ($items as $row) {

                if (false !== strpos((string)$row->id, 'tag:blogger.com')
                   && false === strpos((string)$row->id, '.post-')
                ) {
                    continue;
                }

                $categories = [];
                $url = '';

                foreach ($row as $k => $v) {
                    try {
                        if (is_array($v)) {
                            $row->$k = '';
                        } else {
                            $value = (string)$v;

                            if (is_object($v) && $v instanceof \SimpleXMLElement) {
                                if ($k == 'category' && !$value) {
                                    $attributes = $v->attributes();
                                    if (isset($attributes['term'])) {
                                        $category = (string)$attributes['term'];
                                        if ($category && false === strpos($category, 'http://schemas.google.com')) {
                                            $categories[] = $category;
                                        }
                                    }
                                }

                                if ($k == 'link' && !$value) {

                                    $attributes = $v->attributes();
                                    if (isset($attributes['rel']) && isset($attributes['href'])) {

                                        $rel = (string)$attributes['rel'];
                                        $href = (string)$attributes['href'];

                                        if ($rel == 'alternate' && $href) {
                                            $href = explode('/', $href);
                                            $url = $href[count($href) - 1];
                                        }
                                    }
                                }
                            }

                            $row->$k = $value;

                        }
					// phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                    } catch (LocalizedException $e) {
                    }
                }

                $row = json_decode(json_encode($row), true);

                if (count($categories)) {
                    $row['_categories'] = implode(',', $categories);
                } else {
                    $row['_categories'] = '';
                }

                if ($url) {
                    $row['_url'] = $url;
                } else {
                    $row['_url'] = '';
                }

                if (!$i) {
                    $data[] = array_keys($row);
                }

                $data[] = $row;
                $i++;
                if ($rowsCount && $i >= $rowsCount) {
                    break;
                }
            }
            return $data;
        } else {
            throw new LocalizedException("Cannot read XML file.", 1);
        }
    }

    /**
     * Get CSV delimeter.
     *
     * @param string $file
     * @return string
     */
    protected function getDelimeter($file): string
    {
        $delimeters = [';', ',', '|'];
        foreach ($delimeters as $delimeter) {
            $handle = fopen($file, "r");
            if ($handle !== false) {
                $row = fgetcsv($handle, 0, $delimeter, '"', '\\');
                if ($row !== false) {
                    if (count($row) > 2) {
                        fclose($handle);
                        return $delimeter;
                    }
                }
            }
        }

        fclose($handle);
        return ',';
    }

    /**
     * Execute the process for importing and managing blog post data.
     *
     * @return void
     * @throws LocalizedException
     */
	// phpcs:disable Generic.Metrics.NestingLevel
    public function execute(): void
    {
        $rows = $this->getRows();
        $column = $this->getData('column');
        foreach ($rows as $number => $row) {
            if (!$number) {
                continue;
            }

            $data = [];
            $i = 0;
            foreach ($row as $key => $value) {
                $value = is_string($value) ? trim($value) : '';
                if ('' !== $value) {
                    $field = isset($column[$i]) ? $column[$i] : '';
                    $data[$field] = $value;
                }
                $i++;
            }

            if (isset($data['content'])) {
                $data['content'] = $this->parseContent((string)$data['content']);
            }

            foreach (['categories', 'tags', 'store_ids'] as $fieldName) {
                if (!empty($data[$fieldName]) && is_string($data[$fieldName])) {
                    $data[$fieldName] = explode(',', $data[$fieldName]);

                    foreach ($data[$fieldName] as $fk => $fv) {
                        $fv = trim($fv);
                        if (!$fv) {
                            unset($data[$fieldName][$fk]);
                        } else {
                            $data[$fieldName][$fk] = $fv;
                        }
                    }
                }
            }
            if (!empty($data['related_posts'])) {
                $data['links']['post'] = json_decode($data['related_posts'], true);
                if (is_array($data['links']['post'])) {
                    $data['links']['post'] = array_flip($data['links']['post']);
                }
            }
            if (!empty($data['related_products'])) {
                $data['links']['product'] = json_decode($data['related_products'], true);
                if (is_array($data['links']['product'])) {
                    $data['links']['product'] = array_flip($data['links']['product']);
                }
            }

            if (!empty($data['identifier'])) {
                $identifierInfo = explode('/', $data['identifier']);
                $data['identifier'] = end($identifierInfo);
                $data['identifier'] = str_replace('.html', '', $data['identifier']);
            }

            if (empty($data['store_ids'])) {
                $data['store_ids'] = [$this->getStoreId()];
            }

            if (empty($data['existing_posts']) && !isset($data['is_active']) || $data['is_active'] != 0) {
                $data['is_active'] = 1;
            }

            if (!empty($data['featured_img'])
                && 0 === strpos($data['featured_img'], 'https://')
            ) {
                $data['featured_img'] = $this->getFeaturedImgBySrc($data['featured_img']);
            }

            if (!empty($data['featured_img'])
                && false === strpos($data['featured_img'], \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/')
            ) {
                $data['featured_img'] = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $data['featured_img'];
            }

            if (isset($data['update_time'])) {
                unset($data['update_time']);
            }

            if (!empty($data['media_gallery'])) {
                $data['media_gallery'] = str_replace(
                    [',','|', ' '],
                    [';',';',''],
                    $data['media_gallery']
                );

                if ($data['media_gallery']) {
                    $gallery = [];
                    foreach (explode(';', $data['media_gallery']) as $image) {
                        if ($image) {
                            $gallery[] = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $image;
                        }
                    }
                    $data['media_gallery'] = implode(';', $gallery);
                }
            }

            /* Check categories */
            if (isset($data['categories']) && count($data['categories'])) {
                foreach ($data['categories'] as $key => $dataCategory) {

                    $categoryId = null;

                    $category = $this->_categoryFactory->create();
                    $category->load($dataCategory);
                    if ($category->getId()) {
                        $categoryId = $category->getId();
                    } else {
                        $category = $this->_categoryFactory->create();
                        $category->load($dataCategory, 'title');

                        if ($category->getId()) {
                            $categoryId = $category->getId();
                        } else {
                            $category->setData([
                                'title' => $dataCategory
                            ]);

                            try {
                                $category->save();
                                $categoryId = $category->getId();
							// phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                            } catch (LocalizedException  $e) {
                                /* Do nothing */
                            }
                        }
                    }
                    if ($categoryId) {
                        $data['categories'][$key] = $categoryId;
                    } else {
                        unset($data['categories'][$key]);
                    }
                }
            }

            /* Check tags */
            if (isset($data['tags']) && count($data['tags'])) {
                foreach ($data['tags'] as $key => $dataTag) {

                    $tagId = null;

                    $tag = $this->_tagFactory->create();
                    $tag->load($dataTag);
                    if ($tag->getId()) {
                        $tagId = $tag->getId();
                    } else {
                        $tag = $this->_tagFactory->create();
                        $tag->load($dataTag, 'title');

                        if ($tag->getId()) {
                            $tagId = $tag->getId();
                        } else {
                            $tag->setData([
                                'title' => $dataTag
                            ]);

                            try {
                                $tag->save();
                                $tagId = $tag->getId();
							// phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                            } catch (LocalizedException  $e) {
                                /* Do nothing */
                            }
                        }
                    }
                    if ($tagId) {
                        $data['tags'][$key] = $tagId;
                    } else {
                        unset($data['tags'][$key]);
                    }
                }
            }

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                if (!empty($data['existing_posts'])) {
                    $post->load($data['existing_posts']);
                    if ($post->getId()) {
                        if (($this->getStoreId() !== 0 || !$post->getStoreId())
                            || $post->getStoreId() != $this->getStoreId()
                        ) {
                            $data['store_id'] = $this->getStoreId();
                            unset($data['store_ids']);
                        }
                        if ($post->getData('is_active')) {
                            unset($data['is_active']);
                        }
                        unset($data['existing_posts']);
                        $data['use_default'] = array_fill_keys(array_keys($data), 0);
                        $post->addData($data);
                    } else {
                        $this->messageManager->addWarningMessage(
                            "Unable to load post ID: " . $data['existing_posts']
                        );
                        continue;
                    }
                } else {
                    $post->setData($data);
                }

                /* Fix URL duplicate issue */
                if (!$post->getId() && $post->getData('identifier')) {
                    $number = 1;
                    $identifier = $post->getData('identifier');
                    while (true) {
                        $number++;
                        $id = $post->checkIdentifier($post->getData('identifier'), $post->getData('store_ids'));
                        /*if ($id && $id !== $post->getId()) {*/
                        if ($id) {
                            $post->setData('identifier', $identifier . '-' . $number);
                        } else {
                            break;
                        }
                    }
                }

                $post->save();
                $this->_importedPostsCount++;
            } catch (\Magento\FrameworkLocalizedException\LocalizedException  $e) {
                $this->_skippedPosts[] = !empty($data['title']) ? $data['title'] : '';
            }

            unset($post);
        }
    }

    /**
     * Get featured image by src.
     *
     * @param string $content
     * @return array|string|string[]|null
     */
    protected function parseContent(string $content)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fileSystem = $objectManager->create(\Magento\Framework\Filesystem::class);
        $mediaDirectory = $fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

        foreach (['/src="(.*)"/Ui', '/src=\'(.*)\'/Ui'] as $patern) {

            $matches = [];
            preg_match_all($patern, $content, $matches);

            if (!empty($matches[1])) {

                foreach ($matches[1] as $src) {
                    //$src = $matches[1];
                    $imageName = explode('?', $src);
                    $imageName = explode('/', $imageName[0]);
                    $imageName = end($imageName);
                    $imageName = str_replace(['%20', ' '], '-', $imageName);
                    $imageName = urldecode($imageName);
                    $relativePath = 'wysiwyg/blog/' . $imageName;
                    $imageSource = false;
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
     * Prepare import data
     *
     * @param  array $data
     * @return $this
     */
    public function prepareData($data)
    {
        return $this->setData((array)$data);
    }
}
