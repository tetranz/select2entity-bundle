<?php

namespace Tetranz\Select2EntityBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
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
    /** @var EntityManagerInterface */
    protected $em;
    /** @var RouterInterface */
    protected $router;
    /** @var  array */
    protected $config;

    /**
     * @param EntityManagerInterface $em
     * @param RouterInterface        $router
     * @param array                  $config
     */
    public function __construct(EntityManagerInterface $em, RouterInterface $router, $config)
    {
        $this->em = $em;
        $this->router = $router;
        $this->config = $config;
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

            if (!$transformer instanceof DataTransformerInterface) {
                throw new \Exception(sprintf('The custom transformer %s must implement "Symfony\Component\Form\DataTransformerInterface"', get_class($transformer)));
            }

        // add the default data transformer
        } else {
            $transformer = $options['multiple']
                ? new EntitiesToPropertyTransformer($this->em, $options['class'], $options['text_property'], $options['primary_key'])
                : new EntityToPropertyTransformer($this->em, $options['class'], $options['text_property'], $options['primary_key']);
        }

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        // make variables available to the view
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], array_merge($options['remote_params'], [ 'page_limit' => $options['page_limit'] ]));

        $varNames = array_merge(array('multiple', 'placeholder', 'primary_key', 'page_limit'), array_keys($this->config));
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
                'primary_key' => 'id',
                'remote_path' => null,
                'remote_route' => null,
                'remote_params' => array(),
                'multiple' => false,
                'compound' => false,
                'minimum_input_length' => $this->config['minimum_input_length'],
                'page_limit' => $this->config['page_limit'],
                'scroll' => $this->config['scroll'],
                'allow_clear' => $this->config['allow_clear'],
                'delay' => $this->config['delay'],
                'text_property' => null,
                'placeholder' => '',
                'language' => $this->config['language'],
                'required' => false,
                'cache' => $this->config['cache'],
                'cache_timeout' => $this->config['cache_timeout'],
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
