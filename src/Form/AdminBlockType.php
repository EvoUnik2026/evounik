<?php

namespace App\Form;

use App\Entity\Block;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Block Type',
                'choices' => [
                    'Text' => 'text',
                    'Image' => 'image',
                    'Text + Image' => 'text_image',
                    'Diagram' => 'diagram',
                ],
                'placeholder' => 'Selecteer een type',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
        ]);
    }
}
