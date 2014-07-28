<?php
/**
 * Created by PhpStorm.
 * User: ross
 * Date: 7/19/14
 * Time: 8:11 AM
 */

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;

class EntitiesToPropertyTransformer implements DataTransformerInterface
{
    protected $em;
    protected $className;
    protected $textProperty;

    public function __construct(EntityManager $em, $class, $textProperty)
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
    }

    public function transform($entities)
    {
        if (count($entities) == 0) {
            return '';
        }

        $items = array();

        foreach($entities as $entity) {

            $text = is_null($this->textProperty)
                    ? (string) $entity
                    : $entity->{'get' . $this->textProperty}();

            $items[] = $entity->getId() . '|' . $text;
        }

        return implode('|', $items);
    }

    public function reverseTransform($values)
    {
        // $values has a leading comma
        $values = ltrim($values, ',');

        if (null === $values || '' === $values) {
            return array();
        }

        $ids = explode(',', $values);

        // get multiple entities with one query
        $entities = $this->em->createQueryBuilder()
                ->select('entity')
                ->from($this->className, 'entity')
                ->where('entity.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();

        return $entities;
    }
}
