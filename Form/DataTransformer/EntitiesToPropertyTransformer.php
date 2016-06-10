<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Data transformer for multiple mode (i.e., multiple = true)
 *
 * Class EntitiesToPropertyTransformer
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntitiesToPropertyTransformer implements DataTransformerInterface
{
    /** @var EntityManagerInterface */
    protected $em;
    /** @var  string */
    protected $className;
    /** @var  string */
    protected $textProperty;
    /** @var  string */
    protected $primaryKey;

    /**
     * @param EntityManagerInterface $em
     * @param string $class
     * @param string|null $textProperty
     * @param string $primaryKey
     */
    public function __construct(EntityManagerInterface $em, $class, $textProperty = null, $primaryKey = 'id')
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Transform initial entities to array
     *
     * @param mixed $entities
     * @return array
     */
    public function transform($entities)
    {
        if (empty($entities)) {
            return array();
        }

        $data = array();

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($entities as $entity) {
            $text = is_null($this->textProperty)
                ? (string)$entity
                : $accessor->getValue($entity, $this->textProperty);

            $data[$accessor->getValue($entity, $this->primaryKey)] = $text;
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
        if (!is_array($values) || empty($values)) {
            return new ArrayCollection();
        }

        try {
          // get multiple entities with one query
          $entities = $this->em->createQueryBuilder()
              ->select('entity')
              ->from($this->className, 'entity')
              ->where('entity.'.$this->primaryKey.' IN (:ids)')
              ->setParameter('ids', $values)
              ->getQuery()
              ->getResult();
        }
        catch (DriverException $ex) {
          // this will happen if the form submits invalid data
          throw new TransformationFailedException('One or more id values are invalid');
        }

        return $entities;
    }
}
