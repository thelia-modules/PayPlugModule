<?php

namespace PayPlugModule\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class OrderActionForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'order_id',
                TextType::class
            );
    }

    public function getName()
    {
        return "payplugmodule_order_action_form";
    }
}