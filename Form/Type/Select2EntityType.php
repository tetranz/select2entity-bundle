<?php

namespace Tetranz\Select2EntityBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
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
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 *
 * Class Select2EntityType
 * @package Tetranz\Select2EntityBundle\Form\Type
 */
class Select2EntityType extends AbstractType
{
    /** @var ObjectManager */
    protected $em;
    /** @var RouterInterface */
    protected $router;
    /** @var  array */
    protected $config;

    /**
     * @param ObjectManager $em
     * @param RouterInterface        $router
     * @param array                  $config
     */
    public function __construct(ObjectManager $em, RouterInterface $router, $config)
    {
        $this->em = $em;
        $this->router = $router;
        $this->config = $config;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // custom object manager for this entity, override the default entity manager ?
        if(isset($options['object_manager'])) {
            $em = $options['object_manager'];
            if(!$em instanceof ObjectManager) {
                throw new \Exception('The entity manager \'em\' must be an ObjectManager instance');
            }
            // Use the custom manager instead.
            $this->em = $em;
        }

        // add custom data transformer
        if ($options['transformer']) {
            if (!is_string($options['transformer'])) {
                throw new \Exception('The option transformer must be a string');
            }
            if (!class_exists($options['transformer'])) {
                throw new \Exception('Unable to load class: '.$options['transformer']);
            }

            $transformer = new $options['transformer']($this->em, $options['class'], $options['text_property'], $options['primary_key']);

            if (!$transformer instanceof DataTransformerInterface) {
                throw new \Exception(sprintf('The custom transformer %s must implement "Symfony\Component\Form\DataTransformerInterface"', get_class($transformer)));
            }

            // add the default data transformer
        } else {

            $newTagPrefix = isset($options['allow_add']['new_tag_prefix']) ? $options['allow_add']['new_tag_prefix'] : $this->config['allow_add']['new_tag_prefix'];
            $newTagText = isset($options['allow_add']['new_tag_text']) ? $options['allow_add']['new_tag_text'] : $this->config['allow_add']['new_tag_text'];

            $transformer = $options['multiple']
                ? new EntitiesToPropertyTransformer($this->em, $options['class'], $options['text_property'], $options['primary_key'], $newTagPrefix, $newTagText)
                : new EntityToPropertyTransformer($this->em, $options['class'], $options['text_property'], $options['primary_key'], $newTagPrefix, $newTagText);
        }

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        // make variables available to the view
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], array_merge($options['remote_params'], [ 'page_limit' => $options['page_limit'] ]));

        // merge variable names which are only set per instance with those from yml config
        $varNames = array_merge(array('multiple', 'placeholder', 'primary_key', 'autostart'), array_keys($this->config));
        foreach ($varNames as $varName) {
            $view->vars[$varName] = $options[$varName];
        }

        if (isset($options['req_params']) && is_array($options['req_params']) && count($options['req_params']) > 0) {
            $accessor = PropertyAccess::createPropertyAccessor();

            $reqParams = [];
            foreach ($options['req_params'] as $key => $reqParam) {
                $reqParams[$key] = $accessor->getValue($view,  $reqParam . '.vars[full_name]');
            }

            $view->vars['attr']['data-req_params'] = json_encode($reqParams);
        }

        //tags options
        $varNames = array_keys($this->config['allow_add']);
        foreach ($varNames as $varName) {
            if (isset($options['allow_add'][$varName])) {
                $view->vars['allow_add'][$varName] = $options['allow_add'][$varName];
            } else {
                $view->vars['allow_add'][$varName] = $this->config['allow_add'][$varName];
            }
        }

        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }

	    $view->vars['class_type'] = $options['class_type'];
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
                'object_manager'=> null,
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
                'allow_add' => array(
                    'enabled' => $this->config['allow_add']['enabled'],
                    'new_tag_text' => $this->config['allow_add']['new_tag_text'],
                    'new_tag_prefix' => $this->config['allow_add']['new_tag_prefix'],
                    'tag_separators' => $this->config['allow_add']['tag_separators']
                ),
                'delay' => $this->config['delay'],
                'text_property' => null,
                'placeholder' => '',
                'language' => $this->config['language'],
                'required' => false,
                'cache' => $this->config['cache'],
                'cache_timeout' => $this->config['cache_timeout'],
                'transformer' => null,
                'autostart' => true,
                'width' => isset($this->config['width']) ? $this->config['width'] : null,
                'req_params' => array(),
                'property' => null,
                'callback' => null,
                'class_type' => null,
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
