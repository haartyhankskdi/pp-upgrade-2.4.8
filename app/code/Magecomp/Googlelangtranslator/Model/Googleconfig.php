<?php
namespace Magecomp\Googlelangtranslator\Model;

class Googleconfig implements \Magecomp\Googlelangtranslator\Api\GoogletranslateInterface
{
    protected $helper;
    public function __construct(
        \Magecomp\Googlelangtranslator\Helper\Data $helper
    ) {
        $this->helper = $helper; 
    }
    public function GoogleTranslate($storeid){

        try{
            if($this->helper->isEnabled($storeid)){               
                $response = [   
                    'status' => true,
                    'message' => __('Google Language Translate is Enabled.'),
                    'selectedLanguage' => $this->helper->SelectLanguage($storeid),
                    'layout' => $this->helper->SelectLayout($storeid)                    
                ]; 
            }else{
                $response = [   
                    'status' => false,
                    'message' => __('Google Language Translate is Disabled.')
                ]; 

            }

            return json_encode($response);
        }
        catch (\Exception $e)
        {
             $response = [   
                    'status' => false,
                    'message' => $e->getMessage()
                ]; 
                return json_encode($response);
        }

        }

    
}
