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

class EntityToPropertyTransformer implements DataTransformerInterface
{
    protected $em;
    protected $className;
    protected $property;

    public function __construct(EntityManager $em, $class, $property = 'id')
    {
        $this->em = $em;
        $this->className = $class;
        $this->property = $property;
    }

    public function transform($entity)
    {
        if (null === $entity || '' === $entity) {
            return '';
        }

        return $entity->getId() . '|' . $entity->getName();
    }

    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $repo = $this->em->getRepository($this->className);
        return $repo->find($value);
    }
}
