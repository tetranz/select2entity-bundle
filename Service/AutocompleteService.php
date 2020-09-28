<?php

namespace Tetranz\Select2EntityBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AutocompleteService
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ManagerRegistry      $doctrine
     */
    public function __construct(FormFactoryInterface $formFactory, ManagerRegistry $doctrine)
    {
        $this->formFactory = $formFactory;
        $this->doctrine = $doctrine;
    }   

    /**
     * @param Request                  $request
     * @param string|FormTypeInterface $type
     *
     * @return array
     */
    public function getAutocompleteResults(Request $request, $type)
    {
        $form = $this->formFactory->create($type);
        $fieldOptions = $form->get($request->get('field_name'))->getConfig()->getOptions();

        /** @var EntityRepository $repo */
        $repo = $this->doctrine->getRepository($fieldOptions['class']);

        $term = $request->get('q');

        $countQB = $repo->createQueryBuilder('e');
        $countQB
            ->select($countQB->expr()->count('e'))
            ->where('e.'.$fieldOptions['property'].' LIKE :term')
            ->setParameter('term', '%' . $term . '%')
        ;

        $maxResults = $fieldOptions['page_limit'];
        $offset = ($request->get('page', 1) - 1) * $maxResults;

        $resultQb = $repo->createQueryBuilder('e');
        $resultQb
            ->where('e.'.$fieldOptions['property'].' LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults($maxResults)
            ->setFirstResult($offset)
        ;

        if (is_callable($fieldOptions['callback'])) {
            $cb = $fieldOptions['callback'];

            $cb($countQB, $request);
            $cb($resultQb, $request);
        }

        $count = $countQB->getQuery()->getSingleScalarResult();
        $paginationResults = $resultQb->getQuery()->getResult();

        $result = ['results' => null, 'more' => $count > ($offset + $maxResults)];

        $accessor = PropertyAccess::createPropertyAccessor();

        $result['results'] = array_map(function ($item) use ($accessor, $fieldOptions) {
            return ['id' => $accessor->getValue($item, $fieldOptions['primary_key']), 'text' => $accessor->getValue($item, $fieldOptions['property'])];
        }, $paginationResults);

        return $result;
    }
}
