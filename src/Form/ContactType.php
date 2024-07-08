<?php

namespace App\Form;

use App\Model\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Email is a honeypot input
            ->add('email', TextType::class, [
                'required' => false,
                'row_attr' => ['class' => 'email-row']
            ])
            ->add('firstname', TextType::class, ['attr' => ['placeholder' => 'contact.field.firstname.label']])
            ->add('lastname', TextType::class, ['attr' => ['placeholder' => 'contact.field.lastname.label']])
            ->add(Contact::EMAIL_HIDDEN_INPUT, EmailType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'contact.field.email.label']
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'contact.field.phone.label',
                    'pattern' => false,
                    'data-phone-format' => true
                ]])
            ->add('message', TextareaType::class, ['attr' => ['placeholder' => 'contact.field.message.label']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
