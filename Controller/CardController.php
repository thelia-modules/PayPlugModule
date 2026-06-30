<?php

namespace PayPlugModule\Controller;

use PayPlugModule\Model\PayPlugCardQuery;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;

class CardController extends BaseFrontController
{
    public function deleteCurrentCustomerCard(Request $request)
    {
        if (!$request->hasSession()) {
            return $this->generateRedirect('/');
        }

        $session = $request->getSession();
        $customerId = $session->getCustomerUser()->getId();

        if (null !== $card = PayPlugCardQuery::create()->findOneByCustomerId($customerId)) {
            $card->delete();
        }

        return $this->generateRedirect($session->getReturnToUrl());
    }

}