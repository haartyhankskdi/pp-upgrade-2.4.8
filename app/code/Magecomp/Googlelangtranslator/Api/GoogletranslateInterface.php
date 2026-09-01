<?php
namespace Magecomp\Googlelangtranslator\Api;

interface GoogletranslateInterface
{
    /**
     *  Google translate Configuration
     * @param int $storeid
     *  @return string
     */
    public function GoogleTranslate($storeid);
}