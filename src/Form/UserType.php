<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['attr' => ['pattern' => false, 'placeholder' => 'contact.field.firstname.label']])
            ->add('lastname', null, ['attr' => ['placeholder' => 'contact.field.lastname.label']])
            ->add('email', EmailType::class, ['attr' => ['placeholder' => 'contact.field.email.label']])
            ->add('phone', TextType::class, ['attr' => [
                'placeholder' => 'contact.field.phone.label',
                'pattern' => false,
                'data-phone-format' => true
            ]])
            ->add('callbackMailOptIn', CheckboxType::class, [
                'label' => 'Recevoir les mails de rappel de vos dates',
                'help' => 'Tous les dimanches, vous recevrez un mail pour vous rappeler vos dates de nettoyage pour la semaine à venir.<br/> Si vous n\'avez programmé aucune date cette semaine là, vous ne recevrez aucun mail.',
                'required' => false
            ])
            ->add('missingVolunteerMailOptIn', CheckboxType::class, [
                'label' => 'Recevoir les mails quand il manque des bénévoles',
                'help' => 'Tous les 15 jours nous vous enverrons un mail s\'il manque des bénévoles pour les 15 jours à venir. <br/> Ce mail contiendra la liste des créneaux disponibles.',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
