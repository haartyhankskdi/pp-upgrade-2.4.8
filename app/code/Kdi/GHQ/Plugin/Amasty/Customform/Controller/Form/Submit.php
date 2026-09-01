<?php
/**
 * Copyright © No All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\GHQ\Plugin\Amasty\Customform\Controller\Form;
use Kdi\GHQ\Helper\CustomSession;

class Submit
{

    protected $session;

    public function __construct(
        CustomSession $customSession
    ){
        $this->session = $customSession;
    }

    public function beforeExecute(
        \Amasty\Customform\Controller\Form\Submit $subject
    ): array {
        
        if ($subject->getRequest()) {
            $request = $subject->getRequest()->getParams();
            $fields = [
                "suffer_diagnosed",
                "suffer_diagnosed_yes",
                "other_medication",
                "other_medication_yes",
                "have_allergies",
                "have_allergies_yes",
                "registered_gp",
                "registered_gp_surgery",
                "upload_documents_prescriber",
                "upload_documents_prescriber_yes",
                "upload_documents_prescriber_yes_uploaded",
                "prescriber_to_know",
                "prescriber_to_know_yes"
            ];
            $data = [];
            foreach ($fields as $field) {
                if (isset($request[$field])) {
                    $data[$field] = $request[$field];
                }
            }
            // $data = [
            //     "suffer_diagnosed" => $request['suffer_diagnosed'],
            //     "suffer_diagnosed_yes" => $request['suffer_diagnosed_yes'],
            //     "other_medication" => $request['other_medication'],
            //     "other_medication_yes" => $request['other_medication_yes'],
            //     "have_allergies" => $request['have_allergies'],
            //     "have_allergies_yes" => $request['have_allergies_yes'],
            //     "registered_gp" => $request['registered_gp'],
            //     "registered_gp_surgery" => $request['registered_gp_surgery'],
            //     "upload_documents_prescriber" => $request['upload_documents_prescriber'],
            //     "upload_documents_prescriber_yes" => $request['upload_documents_prescriber_yes'],
            //     "upload_documents_prescriber_yes_uploaded" => $request['upload_documents_prescriber_yes_uploaded'],
            //     "prescriber_to_know" => $request['prescriber_to_know'],
            //     "prescriber_to_know_yes" => $request['prescriber_to_know_yes']
            // ];

            

            // $data = [];
        $this->setCookie(json_encode($data));


        }

        return [];
    }

    public function setCookie($value){
        $this->session->set($value);
    }
}

