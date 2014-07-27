<?php
/**
 * Created by PhpStorm.
 * User: ross
 * Date: 7/17/14
 * Time: 9:40 PM
 */

namespace Tetranz\Select2EntityBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Router;
use Tetranz\Select2EntityBundle\Form\DataTransformer\EntitiesToPropertyTransformer;
use Tetranz\Select2EntityBundle\Form\DataTransformer\EntityToPropertyTransformer;

class Select2EntityType extends AbstractType
{
    protected $em;
    protected $router;
    protected $pageLimit;
    protected $minimumInputLength;
    protected $dataType;

    public function __construct(EntityManager $em, Router $router, $minimumInputLength, $pageLimit, $dataType)
    {
        $this->em = $em;
        $this->router = $router;
        $this->minimumInputLength = $minimumInputLength;
        $this->pageLimit = $pageLimit;
        $this->dataType = $dataType;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = $options['multiple']
                ? new EntitiesToPropertyTransformer($this->em, $options['class'])
                : new EntityToPropertyTransformer($this->em, $options['class']);

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->vars['remote_path'] = $options['remote_path']
                ?: $this->router->generate($options['remote_route'], $options['remote_params']);

        $varNames = array('multiple', 'minimum_input_length', 'page_limit', 'data_type');

        foreach($varNames as $varName) {
            $view->vars[$varName] = $options[$varName];
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'class' => null,
                'remote_path' => null,
                'remote_route' => null,
                'remote_params' => array(),
                'multiple' => false,
                'compound' => false,
                'minimum_input_length' => $this->minimumInputLength,
                'page_limit' => $this->pageLimit,
                'data_type' => $this->dataType
            ));
    }

    public function getName()
    {
        return 'tetranz_select2entity';
    }
}
