<?php

namespace App\Form;

use App\Entity\Topic;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminTopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('image', TextType::class, [
                'label' => 'Image URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Summary',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('showOnHomepage', CheckboxType::class, [
                'label' => 'Show on Homepage',
                'required' => false,
            ])
            ->add('blocks', CollectionType::class, [
                'label' => 'Blocks',
                'entry_type' => AdminBlockType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Topic::class,
        ]);
    }
}
