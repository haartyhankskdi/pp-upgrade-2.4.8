<?php


namespace Magenest\SagePay\Api;

interface BuildFormInterface
{
    /**
     * @param mixed $data
     * @return mixed
     */
    public function buildFormSubmit($data);
}
