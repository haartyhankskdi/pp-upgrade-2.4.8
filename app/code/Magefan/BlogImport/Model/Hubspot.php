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
class Hubspot extends Csv
{
    /**
     * Executes the data processing and import of authors, tags, and posts from a given dataset.
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $rows = $this->getRows();
        $column = [];
        foreach ($rows as $number => $row) {
            if (!$number) {
                $_column = array_flip($row);
                foreach ($_column as $key => $value) {
                    $column[strtolower($key)] = $value;
                }
                continue;
            }

            /* Import author */
            $data = [];
            if (isset($column['author'])
                && !empty($row[$column['author']])
                && ($authorFullname = (string)$row[$column['author']])
            ) {
                $authorInfo = explode(' ', $authorFullname);
                $authorData['firstname'] = $authorInfo[0];
                $authorData['lastname'] = isset($authorInfo[1]) ? $authorInfo[1] : $authorInfo[0];

                $author = $this->_authorFactory->create();
                $existingAuthor = $author->getCollection()
                    ->addFieldTofilter('firstname', $authorData['firstname'])
                    ->addFieldTofilter('lastname', $authorData['lastname'])
                    ->setPageSize(1)
                    ->getFirstItem();

                if ($existingAuthor->getId()) {
                    $author = $existingAuthor;
                } else {
                    try {
                        /* Author saving */
                        $author->setData($authorData)->save();
                        $this->_importedAuthorsCount++;
					// phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        /* echo $e->getMessage(); */
                    }
                }

                if ($author->getId()) {
                    $data['author_id'] = $author->getId();
                }
            }

            /* Import tags */
            if (isset($column['tags']) && !empty($row[$column['tags']])) {
                $tags = explode(',', (string)$row[$column['tags']]);
            } else {
                $tags = [];
            }
            $postTags = [];

            foreach ($tags as $tagTitle) {
                $tagData['title'] = trim($tagTitle);
                if (!$tagData['title']) {
                    continue;
                }

                if (is_numeric($tagData['title'])) {
                    $tagData['title'] = 't' . $tagData['title'];
                }

                $tagData['store_ids'] = [0];

                try {
                    /* Initial saving */
                    if (!isset($existingTags[$tagData['title']])) {
                        $tag = $this->_tagFactory->create();
                        $tag->setData($tagData);

                        $currentTag = $tag->getCollection()
                            ->addFieldToFilter('title', $tag->getTitle())
                            ->setPageSize(1)
                            ->getFirstItem();
                        if ($currentTag->getId()) {
                            $tag = $currentTag;
                        } else {
                            $tag->save();
                        }
                        $this->_importedTagsCount++;
                        $postTags[] = $tag->getId();
                        $existingTags[$tag->getTitle()] = $tag;
                        unset($tag);
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->_skippedTags[] = $data['title'];
                    $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
                }
            }

            $data['tags'] = $postTags;

            if (isset($column['state'])) {
                $data['is_active'] = ($row[$column['state']] == 'PUBLISHED');
            } elseif (isset($column['status']) && isset($row[$column['status']])) {
                $data['is_active'] = ($row[$column['status']] == 'PUBLISHED');
            }

            if (empty($data['is_active'])) {
                $data['is_active'] = 0;
            }

            if (isset($column['publish date']) && isset($row[$column['publish date']])) {
                $data['publish_time'] = $row[$column['publish date']];
            }
            if (isset($column['name'])) {
                $data['title'] = $row[$column['name']];
            }
            if (isset($column['post title'])) {
                $data['title'] = $row[$column['post title']];
            }

            if (isset($column['creation date'])) {
                $data['creation_time'] = $row[$column['creation date']];
            } elseif (isset($column['publish date']) && isset($row[$column['publish date']])) {
                $data['creation_time'] = $row[$column['publish date']];
            }

            if (isset($column['last update'])) {
                $data['update_time'] = $row[$column['last update']];
            } elseif (isset($column['last modified date'])) {
                $data['update_time'] = $row[$column['last modified date']];
            }

            if (isset($column['url'])) {
                $postUrl = $row[$column['url']];
            } elseif (isset($column['post url']) && isset($row[$column['post url']])) {
                $postUrl = $row[$column['post url']];
            } else {
                continue;
            }

            if (!empty($row[$column['post body']])) {
                $data['content'] = $html = $this->parseContent((string)$row[$column['post body']]);
            } else {
                $html = file_get_contents($postUrl);

                $previousErrorState = libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="UTF-8">' . '<body>' . $html . '</body>');
                libxml_use_internal_errors($previousErrorState);

                $content = '';
                $noContentInPage = false;
                foreach (['span10 widget-span widget-type-cell '] as $classname) {

                    $finder = new \DomXPath($dom);
                    $nodes = $finder->query("//*[contains(@class, '$classname')]");
                    if (0 === count($nodes)) {
                        $noContentInPage = true;
                        break;
                    }

                    $_content = new \DOMDocument;
                    foreach ($nodes as $child) {
                        $_content->appendChild($_content->importNode($child, true));
                        //break;
                    }
                    $content .= $_content->saveHTML();

                }

                $data['content'] = $this->parseContent($content);
            }

            if (isset($column['featured image url']) && !empty($row[$column['featured image url']])) {
                $data['featured_img'] = $this->getFeaturedImgBySrc($row[$column['featured image url']]);
            }

            if (empty($data['featured_img'])) {
                $data['featured_img'] = $this->getFeaturedImg($html);
            }

            $identifier = explode(
                '/',
                trim($postUrl, '/')
            );
            $identifier = end($identifier);

            $data['identifier'] = $identifier;
            $data['identifier'] = str_replace('.html', '', $data['identifier']);

            $data['store_ids'] = [$this->getStoreId()];

            $data['meta_title'] = (
                isset($column['post seo title'])
                && !empty($row[$column['post seo title']])
            ) ? $row[$column['post seo title']] : '';

            $data['meta_description'] = $row[$column['meta description']];

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();
                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = isset($data['title']) ? $data['title'] : '';
            }

            unset($post);
            unset($author);
        }
    }

    /**
     * Extracts the featured image URL from the given content.
     *
     * @param string $content
     * @return string|null
     */
    protected function getFeaturedImg($content)
    {
        $p = strpos($content, 'post_featured_image');
        if ($p === false) {
            return null;
        }
        $s = ':url(';

        $p = strpos($content, $s, $p);
        $p2 = strpos($content, ')', $p);
        if ($p === false || $p2 === false) {
            return null;
        }

        $p = $p + strlen($s);

        $src = substr($content, $p, $p2 - $p);
        $src = trim($src, "'");

        if ($imageName = $this->getFeaturedImgBySrc($src)) {
            return $imageName;
        }
        return null;
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
        $mediaDirectory = $fileSystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );

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

                    $imageSource = false;
                    $relativePath = 'wysiwyg/blog/' . $imageName;
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
