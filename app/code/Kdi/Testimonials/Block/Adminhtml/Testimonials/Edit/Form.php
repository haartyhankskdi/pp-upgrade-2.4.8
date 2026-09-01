<?php

/**
 * @author Satyam Kumar
 */
namespace Kdi\Testimonials\Block\Adminhtml\Testimonials\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Cms\Block\Adminhtml\Wysiwyg\Images\Content;


class Form extends Generic
{
    protected $_wysiwygConfig;
    protected $_imagesContent;
  
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        Content $imagesContent,
       
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_imagesContent = $imagesContent;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Form Element 
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry(
            "kdi_testimonials_testimonials"
        );

        $form = $this->_formFactory->create([
            "data" => [
                "id" => "edit_form", // 🔥 THIS is what JS needs
                "enctype" => "multipart/form-data",
                "method" => "post",
                "action" => $this->getUrl("*/*/save"),
            ],
        ]);

        $form->setHtmlIdPrefix('testimonials_');
        $form->setUseContainer(false);
        $form->setData('enctype', 'multipart/form-data');


        $fieldset = $form->addFieldset("base_fieldset", [
            "legend" => __("Podcast Chapter Information"),
        ]);

        if ($model->getTestimonialsId()) {
            $fieldset->addField("testimonials_id", "hidden", [
                "name" => "testimonials_id",
                "value" => $model->getTestimonialsId(),
            ]);
        }

        $fieldset->addField("status", "select", [
            "name" => "status",
            "label" => __("Success Story Status"),
            // "required" => true,
            "values" => $this->getstatus(),

        ]);

        $fieldset->addField("review_writer", "text", [
            "name" => "review_writer",
            "label" => __("Review Writer"),
            "required" => true,
        ]);
        $fieldset->addField("title", "text", [
            "name" => "title",
            "label" => __("Title"),
            "required" => true,
        ]);

        $fieldset->addField("message", "text", [
            "name" => "message",
            "label" => __("Patient Message"),
            "required" => true,
        ]);


        $fieldset->addField("review", "editor", [
            "name" => "review",
            "label" => __("Review"),
            "title" => __("Review"),
            "required" => false,
            'wysiwyg' => true,
            'config' => $this->_wysiwygConfig->getConfig(),
        ]);

        $fieldset->addField("review_listing", "editor", [
            "name" => "review_listing",
            "label" => __("Review for Listing"),
            "title" => __("Review for Listing"),
            "required" => false,
            'wysiwyg' => true,
            'config' => $this->_wysiwygConfig->getConfig(),
        ]);


        $imageFieldset = $form->addFieldset("image_fieldset", [
            "legend" => __("Before And After Images "),
        ]);

       

        $imageFieldset->addField(
            'image1',
            'image',
            [
                'name'  => 'image1',
                'label' => __('Custom Image'),
                'title' => __('Custom Image'),
                'note'  => __('Allowed types: jpg, jpeg, gif, png'),
                'value' => 'http://localhost:8080',
                'after_element_html' => '
                    <style>
                        img.small-image-preview {
                            height:100px !important;
                            width:100px !important;
                            object-fit:cover;
                        }
                    </style>
                '
            ]
        );

        // // if ($model->getData('image1')) {
        // //     $mediaUrl = $this->_storeManager->getStore()
        // //         ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        // //     $model->setData('image1', [[
        // //         'name' => $model->getData('image1'),
        // //         'url'  => $mediaUrl . 'kdi/testimonials/' . $model->getData('image1')
        // //     ]]);
        // // }

        $imageFieldset->addField(
            'image2',
            'image',
            [
                'name'  => 'image2',
                'label' => __('Custom Image'),
                'title' => __('Custom Image'),
                'note'  => __('Allowed types: jpg, jpeg, gif, png'),
                'value' => 'http://localhost:8080/'.$model->getImage2(),
                'after_element_html' => '
                    <style>
                        img.small-image-preview {
                            height:100px !important;
                            width:100px !important;
                            object-fit:cover;
                        }
                    </style>
                '
            ]
        );
        
        



        $seoFieldset = $form->addFieldset("seo_fieldset", [
            "legend" => __("Search Engine Optimization"),
        ]);

        $seoFieldset->addField("meta_url", "text", [
            "name" => "url",
            "label" => __("Url"),
            "required" => true,
        ]);

        $seoFieldset->addField("meta_title", "text", [
            "name" => "meta_title",
            "label" => __("Meta Title"),
            "required" => true,
        ]);


        $seoFieldset->addField("meta_desc", "text", [
            "name" => "meta_desc",
            "label" => __("Meta Desc"),
            "required" => true,
        ]);

        $seoFieldset->addField("robots", "select", [
            "name" => "robots",
            "label" => __("Robot"),
            // "required" => true,
            "values" => $this->getRobotstOptions(),

        ]);

        

        $form->setUseContainer(true); 
        $this->setForm($form);

        if ($model) {
            $form->setValues($model->getData());
        }

        return parent::_prepareForm();
    }

    public function getRobotstOptions()
    {
        
        $options = [["value" => "", "label" => __("-- Please Select --")]];
        $options[] = ["value" => "1", "label" => __("INDEX, FOLLOW")];
        $options[] = ["value" => "2", "label" => __("NOINDEX, FOLLOW")];
        $options[] = ["value" => "3", "label" => __("INDEX, NOFOLLOW")];
        $options[] = ["value" => "4", "label" => __("NOINDEX, NOFOLLOW")];
        return $options;
    }


    public function getstatus()
    {
        $options = [["value" => "", "label" => __("-- Please Select --")]];
        $options[] = ["value" => "1", "label" => __("Enable")];
        $options[] = ["value" => "0", "label" => __("Disable")];
        return $options;
    }



   
}
