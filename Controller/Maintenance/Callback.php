<?php

namespace Onfire\PaymarkOE\Controller\Maintenance;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Callback extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    // disable CSRF protection on these inbound routes
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Handle maintenance callback response from Paymark
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $helper = $objectManager->create("\Onfire\PaymarkOE\Helper\Helper");
        $helper->log(__METHOD__ . " execute Paymark OE maintenance callback");

        $params = $this->getRequest()->getParams();
        $helper->log(__METHOD__ . " " .json_encode($params));

        // no trust id, so stop
        if(empty($params) || empty($params['oeTrustId'])) {
            $helper->log(__METHOD__ . " missing parameters");
            return false;
        }

        // confirm signature is correct
        $valid = $helper->validateSignature([
            'oeTrustId' => $params['oeTrustId'],
            'oeTrustStatus' => $params['oeTrustStatus']
        ], $params['signature']);

        if(!$valid) {
            $helper->log(__METHOD__. " signature validation failed for maintenance/callback with trust id: " . $params['oeTrustId']);
            return false;
        }

        $agreementHelper = $objectManager->get("\Onfire\PaymarkOE\Helper\AgreementHelper");
        $agreement = $agreementHelper->getAgreementByToken($params['oeTrustId']);

        // no agreement with the trust id, so stop
        if(empty($agreement)) {
            $helper->log(__METHOD__ . " no agreement for this trust id");
            return false;
        }

        // returns an array, so reset to first item
        $agreement = reset($agreement);

        // delete agreement locally
        $agreementHelper->deleteAgreementToken($agreement);

        $helper->log(__METHOD__ . " agreement deleted");
    }
}
