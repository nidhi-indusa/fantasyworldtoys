<?php
/**
 * Indusa Webservice handling product add/update, send order and update order status
 * Copyright (C) 2017 Indusa
 * 
 * This file included in Indusa/Webservices is licensed under OSL 3.0
 * 
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Indusa\Webservices\Api;

interface ManageApiManagementInterface
{

    /**
     * POST for manageProducts api
     * @param string $param
     * @return string
     */
    
    public function saveapidata();
}
