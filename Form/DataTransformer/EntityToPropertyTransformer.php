<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Data transformer for single mode (i.e., multiple = false)
 *
 * Class EntityToPropertyTransformer
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntityToPropertyTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $em;
    /** @var  string */
    protected $className;
    /** @var  string */
    protected $textProperty;
    /** @var  string */
    protected $primaryKey;

    /**
     * @param EntityManager $em
     * @param string $class
     * @param string|null $textProperty
     * @param string $primaryKey
     */
    public function __construct(EntityManager $em, $class, $textProperty = null, $primaryKey = 'id')
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Transform entity to array
     *
     * @param mixed $entity
     * @return array
     */
    public function transform($entity)
    {
        $data = array();
        if (null === $entity) {
            return $data;
        }
        $accessor = PropertyAccess::createPropertyAccessor();

        $text = is_null($this->textProperty)
            ? (string)$entity
            : $accessor->getValue($entity, $this->textProperty);

        $data[$accessor->getValue($entity, $this->primaryKey)] = $text;

        return $data;
    }

    /**
     * Transform to single id value to an entity
     *
     * @param string $value
     * @return mixed|null|object
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }
        $repo = $this->em->getRepository($this->className);
        $entity = $repo->find($value);
        if (!$entity) {
            return null;
        }

        return $entity;
    }
}
