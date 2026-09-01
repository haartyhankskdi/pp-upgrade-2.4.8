<?php

namespace Kdi\Schema\Block\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category;

class Faq extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    protected $category;

    /**
     * Constructor
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        Category $category,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
        $this->category = $category;
    }

    /**
     * Get current category
     *
     * @return Category|null
     */
    public function getCurrentCategory()
    {
        // return $this->registry->registry('current_category')->getId();
        return $this->registry->registry('current_category');
    }

    /**
     * Get all FAQ questions and answers as an array
     *
     * @return array
     */
    public function getCategoryFaqs()
    {


        $category = $this->getCurrentCategory();
        $faqs = [];

        if ($category) {
            for ($i = 1; $i <= 10; $i++) {
                $question = trim($category->getData("faq_question_$i"));
                $answer = trim($category->getData("faq_answer_$i"));

                if ($question || $answer) {
                    $faqs[] = [
                        'question' => $question,
                        'answer' => $answer,
                    ];
                }
            }
        }

        return $faqs;
    }


    public function getCategory(){
        $id = $this->getCurrentCategory();
        // if ($id) {
            $model = $this->category->load(84);

            echo "<pre>";
            print_r($model->getData());

            echo "Faq question ". $model->getData('faq_question_1');
        // }
    }
}

