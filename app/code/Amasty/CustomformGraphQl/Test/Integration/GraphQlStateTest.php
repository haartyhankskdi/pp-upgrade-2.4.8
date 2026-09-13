<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Amasty Custom Forms GraphQl for Magento 2 (System)
 */

namespace Amasty\CustomformGraphQl\Test\Integration;

use Amasty\Customform\Api\FormRepositoryInterface;
use Magento\GraphQl\App\State\GraphQlStateDiff;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @magentoAppArea graphql
 */
class GraphQlStateTest extends TestCase
{
    /**
     * @var GraphQlStateDiff
     */
    private $graphQlStateDiff;

    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    protected function setUp(): void
    {
        if (!class_exists(GraphQlStateDiff::class)) {
            $this->markTestSkipped('GraphQlStateDiff class is not available on this version of Magento.');
        }

        $this->formRepository = Bootstrap::getObjectManager()->get(FormRepositoryInterface::class);

        $this->graphQlStateDiff = new GraphQlStateDiff($this);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->graphQlStateDiff->tearDown();
        $this->graphQlStateDiff = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getCustomFormQueryProvider
     */
    public function testGetCustomFormState(
        string $query,
        array $variables,
        array $variables2,
        array $authInfo,
        string $operationName,
        string $expected
    ): void {
        $this->graphQlStateDiff
            ->testState($query, $variables, $variables2, $authInfo, $operationName, $expected, $this);
    }

    /**
     * @dataProvider submitCustomFormQueryProvider
     * @magentoDataFixture Amasty_CustomformGraphQl::Test/GraphQl/_files/create_big_custom_form.php
     */
    public function testSubmitCustomFormState(
        string $query,
        string $operationName,
        string $expected
    ): void {
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
        $variables['formDataJson'] = json_encode($formData);

        $this->graphQlStateDiff
            ->testState($query, $variables, [], [], $operationName, $expected, $this);
    }

    private function getCustomFormQueryProvider(): array
    {
        $this->formRepository = Bootstrap::getObjectManager()->get(FormRepositoryInterface::class);
        $formId = (int)$this->formRepository->getByFormCode('feedback')->getFormId();
        return [
            'Get Custom Form' => [
                <<<'QUERY'
                query customform ($formId: Int!) {
                    customform(formId: $formId) {
                        title
                    }
                }
                QUERY,
                ['formId' => $formId],
                [],
                [],
                'getCustomForm',
                '"title":"Feedback form"'
            ]
        ];
    }

    private function submitCustomFormQueryProvider(): array
    {
        return [
            'Submit Custom Form' => [
                <<<'MUTATION'
                mutation AmFormSubmit($formDataJson: String!) {
                    amCustomFormSubmit (
                        input: {
                            form_data:$formDataJson
                        })
                    {
                        status
                    }
                }
                MUTATION,
                'submitCustomForm',
                '"status":200'
            ]
        ];
    }
}
