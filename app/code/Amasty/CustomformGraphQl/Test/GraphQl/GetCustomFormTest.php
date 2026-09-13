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

class GetCustomFormTest extends GraphQlAbstract
{
    public const CUSTOM_FORM_CODE = 'amasty_test';
    public const MAIN_RESPONSE_KEY = 'customform';

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
     * @magentoApiDataFixture Amasty_CustomformGraphQl::Test/GraphQl/_files/create_custom_form.php
     */
    public function testQueryCustomFrom()
    {
        $form = $this->formRepository->getByFormCode(self::CUSTOM_FORM_CODE);
        $formId = (int)$form->getFormId();
        $formCreateDate = $form->getCreatedAt();

        $query = $this->getCustomFromQuery($formId);
        $response = $this->graphQlQuery($query);

        $expectResponseKeysValues = [
            'form_id' => $formId,
            'advanced_date_format' => 'mm/dd/yy',
            'code' => 'amasty_test',
            'created_at' => $formCreateDate,
            'customer_group' => '0,1,3',
            'email_field' => 'full_name',
            'email_field_hide' => true,
            'form_json' => "[[{\"type\":\"textinput\",\"name\":\"full_name\",\"label\":\"Amasty Full name\"}]]",
            'form_title' => "[\"Amasty Page Title\"]",
            'gdpr_enabled' => false,
            'isSurvey' => true,
            'is_form_available' => true,
            'popup_button' => 'popup_test_button',
            'popup_show' => true,
            'send_notification' => true,
            'send_to' => 'amasty_test_email@amasty.com',
            'status' => true,
            'store_id' => '0',
            'submit_button' => 'Amasty Submit',
            'success_message' => 'Amasty Test GraphQl Success Message',
            'success_url' => 'test-amasty.com',
            'title' => 'Graph Ql Amasty Custom Form'
        ];

        // assert main response key and fields values
        $this->assertArrayHasKey(self::MAIN_RESPONSE_KEY, $response);
        $this->assertResponseFields($response[self::MAIN_RESPONSE_KEY], $expectResponseKeysValues);

        // assert additional response fields
        $this->assertNull($response[self::MAIN_RESPONSE_KEY]['advanced_google_key']);
        $this->assertNotEmpty($response[self::MAIN_RESPONSE_KEY]['gdpr_text']);
    }

    private function getCustomFromQuery(int $formId): string
    {
        return <<<QUERY
query {
  customform(formId:$formId){
    advanced_date_format
    advanced_google_key
    code
    created_at
    customer_group
    email_field
    email_field_hide
    form_id
    form_json
    form_title
    gdpr_enabled
    gdpr_text
    isSurvey
    is_form_available
    popup_button
    popup_show
    send_notification
    send_to
    status
    store_id
    submit_button
    success_message
    success_url
    title
  }
}
QUERY;
    }
}
