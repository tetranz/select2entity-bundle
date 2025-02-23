<?php

namespace Nspyke\Select2EntityBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

readonly class AutocompleteService
{
    public function __construct(private FormFactoryInterface $formFactory, private ManagerRegistry $doctrine)
    {
    }

    public function getAutocompleteResults(Request $request, FormTypeInterface|string $type): array
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
            ->setParameter('term', '%'.$term.'%')
        ;

        $maxResults = $fieldOptions['page_limit'];
        $offset = ($request->get('page', 1) - 1) * $maxResults;

        $resultQb = $repo->createQueryBuilder('e');
        $resultQb
            ->where('e.'.$fieldOptions['property'].' LIKE :term')
            ->setParameter('term', '%'.$term.'%')
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

        $result['results'] = array_map(fn ($item) => ['id' => $accessor->getValue($item, $fieldOptions['primary_key']), 'text' => $accessor->getValue($item, $fieldOptions['property'])], $paginationResults);

        return $result;
    }
}
