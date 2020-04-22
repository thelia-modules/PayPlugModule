<?php

namespace PayPlugModule\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class OrderRefundForm extends OrderActionForm
{
    protected function buildForm()
    {
        parent::buildForm();

        $this->formBuilder
            ->add(
                'refund_amount',
                TextType::class
            );
    }

    public function getName()
    {
        return parent::getName().'_refund';
    }
}