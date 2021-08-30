<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Data transformer for multiple mode (i.e., multiple = true)
 *
 * Class EntitiesToPropertyTransformer
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntitiesToPropertyTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    protected $em;
    /** @var  string */
    protected $className;
    /** @var  string */
    protected $textProperty;
    /** @var  string */
    protected $primaryKey;
    /** @var  string */
    protected $newTagPrefix;
    /** @var string  */
    protected $newTagText;
    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param ObjectManager $em
     * @param string $class
     * @param string|null $textProperty
     * @param string $primaryKey
     * @param string $newTagPrefix
     */
    public function __construct(ObjectManager $em, $class, $textProperty = null, $primaryKey = 'id', $newTagPrefix = '__', $newTagText = ' (NEW)')
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
        $this->primaryKey = $primaryKey;
        $this->newTagPrefix = $newTagPrefix;
        $this->newTagText = $newTagText;
        $this->accessor = PropertyAccess::createPropertyAccessor();
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

        foreach ($entities as $entity) {
            $text = is_null($this->textProperty)
                ? (string) $entity
                : $this->accessor->getValue($entity, $this->textProperty);

            if ($this->em->contains($entity)) {
                $value = (string) $this->accessor->getValue($entity, $this->primaryKey);
            } else {
                $value = $this->newTagPrefix . $text;
                $text = $text.$this->newTagText;
            }

            $data[$value] = $text;
        }

        return $data;
    }

    /**
     * Transform array to a collection of entities
     *
     * @param array $values
     * @return array
     */
    public function reverseTransform($values)
    {
        if (!is_array($values) || empty($values)) {
            return array();
        }

        // add new tag entries
        $newObjects = array();
        $tagPrefixLength = strlen($this->newTagPrefix);
        foreach ($values as $key => $value) {
            $cleanValue = substr($value, $tagPrefixLength);
            $valuePrefix = substr($value, 0, $tagPrefixLength);
            if ($valuePrefix == $this->newTagPrefix) {
                $object = new $this->className;
                $this->accessor->setValue($object, $this->textProperty, $cleanValue);
                $newObjects[] = $object;
                unset($values[$key]);
            }
        }

        // get multiple entities with one query
        $entities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->className, 'entity')
            ->where('entity.'.$this->primaryKey.' IN (:ids)')
            ->setParameter('ids', $values)
            ->getQuery()
            ->getResult();

          // this will happen if the form submits invalid data
        if (count($entities) != count($values)) {
            throw new TransformationFailedException('One or more id values are invalid');
        }

        return array_merge($entities, $newObjects);
    }
}
