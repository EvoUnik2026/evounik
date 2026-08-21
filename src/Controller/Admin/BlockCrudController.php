<?php

namespace App\Controller\Admin;

use App\Entity\Block;
use App\Form\AdminBlockType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class BlockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Block::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Block')
            ->setEntityLabelInPlural('Blocks')
            ->setPageTitle('index', 'Blocks')
            ->setPageTitle('new', 'Create New Block')
            ->setPageTitle('edit', 'Edit Block: %entity_label%')
            ->setPaginatorPageSize(25)
            ->setDefaultSort(['position' => 'ASC', 'createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, static function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            yield IdField::new('id')
                ->hideOnForm();
            yield ChoiceField::new('type')
                ->setLabel('Type')
                ->setChoices([
                    'Text' => 'text',
                    'Image' => 'image',
                    'Text + Image' => 'text_image',
                    'Diagram' => 'diagram',
                ]);
            yield TextEditorField::new('content')
                ->setLabel('Content')
                ->formatValue(function ($value) {
                    return substr((string) $value, 0, 50) . '...';
                });
            yield AssociationField::new('topic')
                ->setLabel('Parent Topic');
            yield IntegerField::new('position')
                ->setLabel('Position');
            yield DateTimeField::new('createdAt')
                ->hideOnForm()
                ->setLabel('Created');
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            yield IdField::new('id');
            yield ChoiceField::new('type')
                ->setLabel('Block Type')
                ->setChoices([
                    'Text' => 'text',
                    'Image' => 'image',
                    'Text + Image' => 'text_image',
                    'Diagram' => 'diagram',
                ]);
            yield TextEditorField::new('content')
                ->setLabel('Content');
            yield AssociationField::new('topic')
                ->setLabel('Parent Topic');
            yield IntegerField::new('position')
                ->setLabel('Position');
            yield DateTimeField::new('createdAt')
                ->hideOnForm();
            yield DateTimeField::new('updatedAt')
                ->hideOnForm();
        } else { // EDIT or NEW page
            yield ChoiceField::new('type')
                ->setLabel('Block Type')
                ->setChoices([
                    'Text' => 'text',
                    'Image' => 'image',
                    'Text + Image' => 'text_image',
                    'Diagram' => 'diagram',
                ])
                ->setHelp('Select the type of content block');
            yield TextEditorField::new('content')
                ->setLabel('Block Content')
                ->setHelp('The content of the block (HTML support for text editor)')
                ->setRequired(false);
            yield AssociationField::new('topic')
                ->setLabel('Parent Topic')
                ->setHelp('Select which topic this block belongs to')
                ->setRequired(true);
            yield IntegerField::new('position')
                ->setLabel('Display Position')
                ->setHelp('Display order (lower numbers appear first)')
                ->setRequired(false);
        }
    }
}
