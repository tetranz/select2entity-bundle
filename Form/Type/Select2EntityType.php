<?php

namespace Tetranz\Select2EntityBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Router;
use Tetranz\Select2EntityBundle\Form\DataTransformer\EntitiesToPropertyTransformer;
use Tetranz\Select2EntityBundle\Form\DataTransformer\EntityToPropertyTransformer;

/**
 *
 * Class Select2EntityType
 * @package Tetranz\Select2EntityBundle\Form\Type
 */
class Select2EntityType extends AbstractType
{
    /** @var EntityManager */
    protected $em;
    /** @var Router */
    protected $router;
    /** @var  integer */
    protected $pageLimit;
    /** @var  integer */
    protected $minimumInputLength;

    /**
     * @param EntityManager $em
     * @param Router $router
     * @param integer $minimumInputLength
     * @param integer $pageLimit
     */
    public function __construct(EntityManager $em, Router $router, $minimumInputLength, $pageLimit)
    {
        $this->em = $em;
        $this->router = $router;
        $this->minimumInputLength = $minimumInputLength;
        $this->pageLimit = $pageLimit;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // add the appropriate data transformer
        $transformer = $options['multiple']
            ? new EntitiesToPropertyTransformer($this->em, $options['class'], $options['text_property'])
            : new EntityToPropertyTransformer($this->em, $options['class'], $options['text_property']);

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        // make variables available to the view
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], $options['remote_params']).
            '?page_limit='.$options['page_limit'];

        $varNames = array('multiple', 'minimum_input_length', 'placeholder', 'language');
        foreach ($varNames as $varName) {
            $view->vars[$varName] = $options[$varName];
        }

        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }
    }

    /**
     * Added for pre Symfony 2.7 compatibility
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => null,
                'remote_path' => null,
                'remote_route' => null,
                'remote_params' => array(),
                'multiple' => false,
                'compound' => false,
                'minimum_input_length' => $this->minimumInputLength,
                'page_limit' => $this->pageLimit,
                'text_property' => null,
                'placeholder' => '',
                'language' => 'en',
                'required' => false,
            )
        );
    }

    public function getName()
    {
        return 'tetranz_select2entity';
    }
}
