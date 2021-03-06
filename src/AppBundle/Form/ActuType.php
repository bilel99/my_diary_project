<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActuType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('langue', EntityType::class, [
                'class'         => 'AppBundle\Entity\Langue',
                'placeholder'   => 'Séléctionnez une langue',
                'mapped'        => true,
                'required'      => true
            ])
            ->add('users', EntityType::class, [
                'class'         => 'AppBundle\Entity\Users',
                'placeholder'   => 'Séléctionnez un utilisateur',
                'mapped'        => true,
                'required'      => true
            ])
            ->add('titre', TextType::class)
            ->add('contenu', TextareaType::class)
            ->add('status', ChoiceType::class, array(
                'label' => 'Status',
                'choices' => array('actif' => 'actif', 'innactif' => 'innactif'),
                'expanded' => true,
                'multiple' => false,
                'required' => true
            ))
            ->add('date_debut', DateType::class, [
                'label'  =>'date début',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                // add a class that can be selected in JavaScript
                'attr' => ['class' => 'js-datepicker date_debut'],
                'placeholder' => 'Select a value'
            ])
            ->add('date_fin', DateType::class, [
                'label'  =>'date fin',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                // add a class that can be selected in JavaScript
                'attr' => ['class' => 'js-datepicker date_fin'],
                'placeholder' => 'Select a value'
            ])
            ->add('media', MediaType::class);


        // Si on a une ArrayCollection alors utilser sa et créer formulaire via js
        /*->add('media', CollectionType::class, array(
        'entry_type' => MediaType::class,
        'entry_options' => array('label' => true),
        'allow_add' => true))*/

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Actu'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_actu';
    }


}
