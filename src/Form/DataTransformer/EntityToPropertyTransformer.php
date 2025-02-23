<?php

namespace Nspyke\Select2EntityBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Data transformer for single mode (i.e., multiple = false).
 *
 * Class EntityToPropertyTransformer
 */
readonly class EntityToPropertyTransformer implements DataTransformerInterface
{
    protected PropertyAccessor $accessor;

    public function __construct(protected ObjectManager $em, protected string $className, protected ?string $textProperty = null, protected string $primaryKey = 'id', protected string $newTagPrefix = '__', protected string $newTagText = ' (NEW)')
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Transform entity to array.
     *
     * @return array
     */
    public function transform($entity): array
    {
        $data = [];
        if (empty($entity)) {
            return $data;
        }

        $text = is_null($this->textProperty)
            ? (string) $entity
            : $this->accessor->getValue($entity, $this->textProperty);

        if ($this->em->contains($entity)) {
            $value = (string) $this->accessor->getValue($entity, $this->primaryKey);
        } else {
            $value = $this->newTagPrefix.$text;
            $text .= $this->newTagText;
        }

        $data[$value] = $text;

        return $data;
    }

    /**
     * Transform single id value to an entity.
     *
     * @param string $value
     *
     * @return mixed|object|null
     */
    public function reverseTransform($value): mixed
    {
        if (empty($value) || !$this->em instanceof EntityManagerInterface) {
            return null;
        }

        // Add a potential new tag entry
        $tagPrefixLength = strlen($this->newTagPrefix);
        $cleanValue = substr($value, $tagPrefixLength);
        $valuePrefix = substr($value, 0, $tagPrefixLength);
        if ($valuePrefix == $this->newTagPrefix) {
            // In that case, we have a new entry
            $entity = new $this->className();
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
            } catch (\Doctrine\ORM\UnexpectedResultException) {
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
