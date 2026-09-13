<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Amasty Custom Forms GraphQl for Magento 2 (System)
 */

namespace Amasty\CustomformGraphQl\Test\GraphQl;

use Amasty\Customform\Api\FormRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class SubmitCustomFormTest extends GraphQlAbstract
{
    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formRepository = Bootstrap::getObjectManager()->get(FormRepositoryInterface::class);
    }

    /**
     * @group amasty_customform
     *
     * @magentoApiDataFixture Amasty_CustomformGraphQl::Test/GraphQl/_files/create_big_custom_form.php
     */
    public function testSubmitCustomFrom()
    {
        $formId = (int)$this->formRepository->getByFormCode('amasty_big_form_test')->getFormId();

        $formData = [
            'form_id' => $formId,
            'textinput-amasty' => 'Amasty Test Text Input',
            'number-amasty' => '1234567',
            'date-amasty' => date('m/d/Y'),
            'dropdown-amasty' => 'am-option-2',
            'checkbox-amasty' => 'am-checkbox-1',
            'radio-amasty' => 'am-radio-1',
            'rating-amasty' => 'am-star-4'
        ];

        $query = $this->getSubmitCustomFromMutation();
        $response = $this->graphQlMutation($query, ['formDataJson' => json_encode($formData)]);

        $this->assertEquals(200, $response['amCustomFormSubmit']['status']);
    }

    private function getSubmitCustomFromMutation(): string
    {
        return <<<'MUTATION'
mutation AmFormSubmit($formDataJson: String!)
{
  amCustomFormSubmit (
    input: {
      form_data:$formDataJson
    })
  {
    status
  }
}
MUTATION;
    }
}
