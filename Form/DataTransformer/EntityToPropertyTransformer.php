<?php

namespace Tetranz\Select2EntityBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Data transformer for single mode (i.e., multiple = false)
 *
 * Class EntityToPropertyTransformer
 *
 * @package Tetranz\Select2EntityBundle\Form\DataTransformer
 */
class EntityToPropertyTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    protected $em;
    /** @var  string */
    protected $className;
    /** @var  string */
    protected $textProperty;
    /** @var  string */
    protected $primaryKey;
    /** @var string  */
    protected $newTagPrefix;
    /** @var string  */
    protected $newTagText;
    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param ObjectManager $em
     * @param string                 $class
     * @param string|null            $textProperty
     * @param string                 $primaryKey
     * @param string                 $newTagPrefix
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
     * Transform entity to array
     *
     * @param mixed $entity
     * @return array
     */
    public function transform($entity)
    {
        $data = array();
        if (empty($entity)) {
            return $data;
        }

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

        return $data;
    }

    /**
     * Transform single id value to an entity
     *
     * @param string $value
     * @return mixed|null|object
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return null;
        }

        // Add a potential new tag entry
        $tagPrefixLength = strlen($this->newTagPrefix);
        $cleanValue = substr($value, $tagPrefixLength);
        $valuePrefix = substr($value, 0, $tagPrefixLength);
        if ($valuePrefix == $this->newTagPrefix) {
            // In that case, we have a new entry
            $entity = new $this->className;
            $this->accessor->setValue($entity, $this->textProperty, $cleanValue);
        } else {
            // We do not search for a new entry, as it does not exist yet, by definition
            try {
                $entity = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from($this->className, 'entity')
                    ->where('entity.'.$this->primaryKey.' = :id')
                    ->setParameter('id', $value)
                    ->getQuery()
                    ->getSingleResult();
            }
            catch (\Doctrine\ORM\UnexpectedResultException $ex) {
                // this will happen if the form submits invalid data
                throw new TransformationFailedException(sprintf('The choice "%s" does not exist or is not unique', $value));
            }
        }

        if (!$entity) {
            return null;
        }

        return $entity;
    }
}
