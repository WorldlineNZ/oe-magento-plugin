<?php

namespace Paymark\PaymarkOE\Api;

/**
 * @api
 */
interface StatusManagementInterface
{
    /**
     * Return the transaction status for the OE request
     *
     * @api
     * @return array
     */
    public function getTransactionStatus();
}
