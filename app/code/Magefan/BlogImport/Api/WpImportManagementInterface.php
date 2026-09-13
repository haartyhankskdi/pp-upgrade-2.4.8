<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Api;

interface WpImportManagementInterface
{

    /**
     * Import WP posts
     *
     * @return mixed
     */
    public function wpImport();
}
