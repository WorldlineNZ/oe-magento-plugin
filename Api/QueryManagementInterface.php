<?php

namespace Paymark\PaymarkOE\Api;

/**
 * @api
 */
interface QueryManagementInterface
{
    /**
     * Return the transaction status for the OE request
     *
     * @api
     * @return string
     */
    public function getTransactionDetails();
}
