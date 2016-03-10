<?php

namespace Tetranz\Select2EntityBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
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
    /** @var  boolean */
    protected $allowClear;
    /** @var  integer */
    protected $delay;
    /** @var  string */
    protected $language;
    /** @var  boolean */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param Router $router
     * @param integer $minimumInputLength
     * @param integer $pageLimit
     * @param boolean $allowClear
     * @param integer $delay
     * @param string $language
     * @param boolean $cache
     */
    public function __construct(EntityManager $em, RouterInterface $router, $minimumInputLength, $pageLimit, $allowClear, $delay, $language, $cache)
    {
        $this->em = $em;
        $this->router = $router;
        $this->minimumInputLength = $minimumInputLength;
        $this->pageLimit = $pageLimit;
        $this->allowClear = $allowClear;
        $this->delay = $delay;
        $this->language = $language;
        $this->cache = $cache;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // add custom data transformer
        if ($options['transformer']) {
            if (!is_string($options['transformer'])) {
                throw new \Exception('The option transformer must be a string');
            }
            if (!class_exists($options['transformer'])) {
                throw new \Exception('Unable to load class: '.$options['transformer']);
            }

            $transformer = new $options['transformer']($this->em, $options['class']);

            $isValidType = $options['multiple']
                ? ($transformer instanceof EntitiesToPropertyTransformer)
                : ($transformer instanceof EntityToPropertyTransformer);
            if (!$isValidType) {
                throw new \Exception(sprintf('The custom transformer %s must extend %s', get_class($transformer), $options['multiple'] ? EntitiesToPropertyTransformer::class : EntityToPropertyTransformer::class));
            }

        // add the default data transformer
        } else {
            $transformer = $options['multiple']
                ? new EntitiesToPropertyTransformer($this->em, $options['class'], $options['text_property'])
                : new EntityToPropertyTransformer($this->em, $options['class'], $options['text_property']);
        }

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        // make variables available to the view
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], array_merge($options['remote_params'], [ 'page_limit' => $options['page_limit'] ]));

        $varNames = array('multiple', 'minimum_input_length', 'placeholder', 'language', 'allow_clear', 'delay', 'language', 'cache');
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
                'allow_clear' => $this->allowClear,
                'delay' => $this->delay,
                'text_property' => null,
                'placeholder' => '',
                'language' => $this->language,
                'required' => false,
                'cache' => $this->cache,
                'transformer' => null,
            )
        );
    }

    /**
     * pre Symfony 3 compatibility
     *
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * Symfony 2.8+
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'tetranz_select2entity';
    }
}
