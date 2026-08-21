<?php

namespace App\Controller\Admin;

use App\Entity\Topic;
use App\Form\AdminTopicType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class TopicCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Topic::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Topic')
            ->setEntityLabelInPlural('Topics')
            ->setPageTitle('index', 'Topics')
            ->setPageTitle('new', 'Create New Topic')
            ->setPageTitle('edit', 'Edit Topic: %entity_label%')
            ->setPaginatorPageSize(20)
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
            yield TextField::new('title')
                ->setLabel('Topic Title');
            yield TextField::new('slug')
                ->setLabel('URL Slug');
            yield BooleanField::new('showOnHomepage')
                ->setLabel('On Homepage');
            yield IntegerField::new('position')
                ->setLabel('Position');
            yield DateTimeField::new('createdAt')
                ->hideOnForm()
                ->setLabel('Created');
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            yield IdField::new('id');
            yield TextField::new('title')
                ->setLabel('Topic Title');
            yield TextField::new('slug')
                ->setLabel('URL Slug');
            yield TextField::new('image')
                ->setLabel('Image URL');
            yield TextEditorField::new('summary')
                ->setLabel('Summary');
            yield TextEditorField::new('description')
                ->setLabel('Description');
            yield IntegerField::new('position')
                ->setLabel('Position');
            yield BooleanField::new('showOnHomepage')
                ->setLabel('Show on Homepage');
            yield AssociationField::new('blocks')
                ->setLabel('Content Blocks');
            yield DateTimeField::new('createdAt')
                ->hideOnForm();
            yield DateTimeField::new('updatedAt')
                ->hideOnForm();
        } else { // EDIT or NEW page
            yield TextField::new('title')
                ->setLabel('Topic Title')
                ->setHelp('The title of the topic');
            yield TextField::new('slug')
                ->setLabel('URL Slug')
                ->setHelp('URL-friendly identifier (auto-generated from title)');
            yield TextField::new('image')
                ->setLabel('Image URL')
                ->setHelp('Image URL or path')
                ->setRequired(false);
            yield TextEditorField::new('summary')
                ->setLabel('Summary')
                ->setHelp('Short summary of the topic')
                ->setRequired(false);
            yield TextEditorField::new('description')
                ->setLabel('Full Description')
                ->setHelp('Full description of the topic')
                ->setRequired(false);
            yield IntegerField::new('position')
                ->setLabel('Display Position')
                ->setHelp('Display order (lower numbers appear first)');
            yield BooleanField::new('showOnHomepage')
                ->setLabel('Show on Homepage')
                ->setHelp('Make this topic visible on the homepage');
            yield AssociationField::new('blocks')
                ->setLabel('Content Blocks')
                ->setHelp('Add or manage content blocks for this topic')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ]);
        }
    }
}
