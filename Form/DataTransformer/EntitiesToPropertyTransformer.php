<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Data transformer for multiple mode (i.e., multiple = true)
 *
 * Class EntitiesToPropertyTransformer
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntitiesToPropertyTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $em;
    /** @var  string */
    protected $className;
    /** @var  string */
    protected $textProperty;

    /**
     * @param EntityManager $em
     * @param string $class
     * @param string|null $textProperty
     */
    public function __construct(EntityManager $em, $class, $textProperty = null)
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
    }

    /**
     * Transform initial entities to array
     *
     * @param mixed $entities
     * @return array
     */
    public function transform($entities)
    {
        if (is_null($entities) || count($entities) === 0) {
            return array();
        }

        $data = array();

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($entities as $entity) {
            $text = is_null($this->textProperty)
                ? (string)$entity
                : $accessor->getValue($entity, $this->textProperty);

            $data[$accessor->getValue($entity, 'id')] = $text;
        }

        return $data;
    }

    /**
     * Transform array to a collection of entities
     *
     * @param array $values
     * @return ArrayCollection
     */
    public function reverseTransform($values)
    {
        if (!is_array($values) || count($values) === 0) {
            return new ArrayCollection();
        }

        // get multiple entities with one query
        $entities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->className, 'entity')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $values)
            ->getQuery()
            ->getResult();

        return $entities;
    }
}
