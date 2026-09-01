<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
namespace Magefan\Blog\Block\Post\View;

use Magento\Store\Model\ScopeInterface;

/**
 * Blog post view rich snippets
 */
class Richsnippets extends Opengraph
{
    /**
     * @param  array
     */
    protected $_options;

    /**
     * Retrieve snipet params
     *
     * @return array
     */

    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magefan\Blog\Model\Post $post,
    \Magento\Framework\Registry $coreRegistry,
    \Magento\Cms\Model\Template\FilterProvider $filterProvider,
    \Magefan\Blog\Model\PostFactory $postFactory,
    \Magefan\Blog\Model\Url $url,
    \Haartyhanks\AuthReview\Model\Entity $authModel,
    array $data = [],
    $config = null,
    $templatePool = null
) {
    parent::__construct($context, $post, $coreRegistry, $filterProvider, $postFactory, $url, $data, $config, $templatePool);
    $this->authModel = $authModel;
}

    public function getTeamInfo($id)
    {
        $data =  $this->authModel->load($id);
        if ($data) {
            return $data->getData();
        }
        return [];
    }
    public function getOptions()
    {
        if ($this->_options === null) {
            $post = $this->getPost();

            // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/blog_post_richsnippets.log');
            // $zendLogger = new \Zend_Log();
            // $zendLogger->addWriter($writer);
            // $zendLogger->info(" Message Log " . print_r([
            //     'post' => $post['post_id'],
            //     'status' => $post['is_active_google_eeat'],
            //     'review' => $post['post_review_id'],
            //     'author' => $post['post_author_id'],
            // ], true));
    
            $logoBlock = $this->getLayout()->getBlock('logo');
            if (!$logoBlock) {
                $logoBlock = $this->getLayout()->getBlock('amp.logo');
            }
    
            $imageUrl = $this->getImage() ?: ($logoBlock ? $logoBlock->getLogoSrc() : '');


            $author = [
                'name' => 'HARMINDER ‘HARMY’ KAUR',
                'jobTitle' => 'BSc(hons) Pharmacy',
                'identifier' => '2061107',
                'url' => 'https://www.pharmacyplanet.com/team/'
            ];
            $reviewedBy = [
                'name' => 'GURDEV SEHMI',
                'jobTitle' => 'BSc Pharm, MRPharmS, Independent Prescriber, Superintendent Pharmacist, Clinical Lead',
                'identifier' => '2050925',
                'url' => 'https://www.pharmacyplanet.com/team/'
            ];

            if ($post['is_active_google_eeat']) {
                $author = $this->getTeamInfo($post['post_author_id']);
                $reviewedBy = $this->getTeamInfo($post['post_review_id']);
                $author['name'] = $author['name'] ?? 'HARMINDER ‘HARMY’ KAUR';
                $author['jobTitle'] = $author['job_title'] ?? 'BSc(hons) Pharmacy';
                $author['identifier'] = $author['identifier'] ?? '2061107';
                $author['url'] = $author['url'] ?? 'https://www.pharmacyplanet.com/team/';

                $reviewedBy['name'] = $reviewedBy['name'] ?? 'GURDEV SEHMI';
                $reviewedBy['jobTitle'] = $reviewedBy['job_title'] ?? 'BSc Pharm, MRPharmS, Independent Prescriber, Superintendent Pharmacist, Clinical Lead';
                $reviewedBy['identifier'] = $reviewedBy['identifier'] ?? '2050925';
                $reviewedBy['url'] = $reviewedBy['url'] ?? 'https://www.pharmacyplanet.com/team/';
            }
    
            $this->_options = [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@type" => "MedicalWebPage",
                        "@id" => $post->getPostUrl() . '#webpage',
                        "url" => $post->getPostUrl(),
                        "name" => $post->getTitle(),
                        "headline" => $post->getTitle(),
                        "description" => $post->getMetaDescription() ?: $post->getShortContent(),
                        "inLanguage" => "en-GB",
                        "datePublished" => $post->getPublishDate('c'),
                        "dateModified" => $post->getUpdateDate('c'),
                        "lastReviewed" => $post->getUpdateDate('c'),
                        "image" => $this->getFeaturedImage(),
    
                        "mainEntityOfPage" => [
                            "@type" => "WebPage",
                            "@id" => $post->getPostUrl()
                        ],
    
                        "about" => [
                            "@type" => "MedicalTherapy",
                            "name" => $this->getMedicalTopic()
                        ],
    
                        "audience" => [
                            "@type" => "MedicalAudience",
                            "audienceType" => "Patient"
                        ],
    
                        "medicalAudience" => [
                            "@type" => "MedicalAudience",
                            "audienceType" => "Patient"
                        ],
    
                        "author" => [
                            "@type" => "Person",
                            "name" => $author['name'],
                            "jobTitle" => $author['jobTitle'],
                            "identifier" => $author['identifier'],
                            "url" => $author['url'],
                        ],
    
                        "reviewedBy" => [
                            "@type" => "Person",
                            "name" => $reviewedBy['name'],
                            "jobTitle" => $reviewedBy['jobTitle'],
                            "identifier" => $reviewedBy['identifier'],
                            "url" => $reviewedBy['url'],
                        ],
    
                        "publisher" => [
                            "@id" => "https://www.pharmacyplanet.com/#organization"
                        ],
    
                        "isPartOf" => [
                            "@id" => "https://www.pharmacyplanet.com/#website"
                        ],
    
                        "breadcrumb" => [
                            "@id" => $post->getPostUrl() . '#breadcrumb'
                        ]
                    ],
    
                    // New: BlogPosting node
                    [
                        "@type" => "BlogPosting",
                        "@id" => $post->getPostUrl() . '#blogposting',
                        "headline" => $this->getTitle(),
                        "description" => $this->getDescription(),
                        "inLanguage" => "en-GB",
                        "datePublished" => $post->getPublishDate('c'),
                        "dateModified" => $post->getUpdateDate('c'),
                        "url" => $post->getPostUrl(),
    
                        "image" => [
                            "@type" => "ImageObject",
                            "url" => $imageUrl,
                            "width" => 720,
                            "height" => 720,
                        ],
    
                        "author" => [
                            "@type" => "Person",
                            "name" => $author['name'],
                            "jobTitle" => $author['jobTitle'],
                            "identifier" => $author['identifier'],
                            "url" => $author['url'],
                        ],
    
                        "publisher" => [
                            "@id" => "https://www.pharmacyplanet.com/#organization"
                        ],
    
                        "mainEntityOfPage" => [
                            "@id" => $post->getPostUrl() . '#webpage'
                        ],
    
                        "isPartOf" => [
                            "@id" => "https://www.pharmacyplanet.com/#website"
                        ]
                    ],
    
                    // Static
                    [
                        "@type" => "Organization",
                        "@id" => "https://www.pharmacyplanet.com/#organization",
                        "name" => "Pharmacy Planet",
                        "url" => "https://www.pharmacyplanet.com/",
                        "logo" => [
                            "@type" => "ImageObject",
                            "url" => $this->getLogoUrl()
                        ]
                    ],
    
                    // Static
                    [
                        "@type" => "WebSite",
                        "@id" => "https://www.pharmacyplanet.com/#website",
                        "url" => "https://www.pharmacyplanet.com/",
                        "name" => "Pharmacy Planet",
                        "publisher" => [
                            "@id" => "https://www.pharmacyplanet.com/#organization"
                        ]
                    ],
                    $this->getBreadcrumbSchema(),
    
                    [
                        "@type" => "FAQPage",
                        "@id" => $post->getPostUrl() . '#faq',
                        "mainEntity" => $this->getFaqSchema()
                    ]
                ]
            ];
        }
    
        return $this->_options;
    }

    /**
     * Retrieve author name
     *
     * @return array
     */
    public function getAuthor()
    {
        if ($author = $this->getPost()->getAuthor()) {
            if ($author->getTitle()) {
                return $author->getTitle();
            }
        }

        // if no author name return name of publisher
        return $this->getPublisher();
    }

    /**
     * Retrieve publisher name
     *
     * @return array
     */
    public function getPublisher()
    {
        $publisher =  $this->_scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        );

        if (!$publisher) {
            $publisher = 'Magento2 Store';
        }

        return $publisher;
    }

    /**
     * Render html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '<script type="application/ld+json">'
            . json_encode($this->getOptions())
            . '</script>';
    }


    public function getBreadcrumbSchema()
    {
        $post = $this->getPost();
 
        return [
            "@type" => "BreadcrumbList",
            "@id" => $post->getPostUrl() . '#breadcrumb',
            "itemListElement" => $this->getBreadcrumbItems()
        ];
    }

    public function blogPosting(){
        $post = $this->getPost();

            $logoBlock = $this->getLayout()->getBlock('logo');
            if (!$logoBlock) {
                $logoBlock = $this->getLayout()->getBlock('amp.logo');
            }
$logoUrl = $this->getImage() ?: ($logoBlock ? $logoBlock->getLogoSrc() : null);
            $lastReviewed = date('c', strtotime($post->getPublishDate())); // ISO 8601 format
        $this->_options =  [
            '@context' => 'http://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $post->getPostUrl(),
            'author' => $this->getAuthor(),
            'headline' => $this->getTitle(),
            'description' => $this->getDescription(),
            'datePublished' => $post->getPublishDate('c'),
            'dateModified' => $post->getUpdateDate('c'),
            'image' => [
                '@type' => 'ImageObject',
                'url' => $this->getImage() ?:
                    ($logoBlock ? $logoBlock->getLogoSrc() : ''),
                'width' => 720,
                'height' => 720,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->getPublisher(),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoBlock ? $logoBlock->getLogoSrc() : '',
                ],
            ],
            'mainEntityOfPage' => $this->_url->getBaseUrl(),
        ];
    }

    public function getBreadcrumbItems()
    {
        $post = $this->getPost();
 
        $items = [];
        $position = 1;
 
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => "Home",
            "item" => $this->_url->getBaseUrl()
        ];
 
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => "Blog",
            "item" => rtrim($this->_url->getBaseUrl(), '/') . '/blog'
        ];
 
        if ($this->getCategoryName() && $this->getCategoryUrl()) {
            $items[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $this->getCategoryName(),
                "item" => $this->getCategoryUrl()
            ];
        }
 
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $post->getTitle(),
            "item" => $post->getPostUrl()
        ];
 
        return $items;
    }

    
}
