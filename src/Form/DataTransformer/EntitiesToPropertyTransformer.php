<?php

namespace Nspyke\Select2EntityBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Data transformer for multiple mode (i.e., multiple = true).
 *
 * Class EntitiesToPropertyTransformer
 */
readonly class EntitiesToPropertyTransformer implements DataTransformerInterface
{
    protected PropertyAccessor $accessor;

    public function __construct(
        protected ObjectManager $em,
        protected string $className,
        protected ?string $textProperty = null,
        protected string $primaryKey = 'id',
        protected string $newTagPrefix = '__',
        protected string $newTagText = ' (NEW)',
    ) {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Transform initial entities to array.
     */
    public function transform(mixed $entities): array
    {
        if (empty($entities)) {
            return [];
        }

        $data = [];

        foreach ($entities as $entity) {
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
        }

        return $data;
    }

    /**
     * Transform array to a collection of entities.
     *
     * @param array $values
     */
    public function reverseTransform(mixed $values): array
    {
        if (!is_array($values) || empty($values) || !$this->em instanceof EntityManagerInterface) {
            return [];
        }

        // add new tag entries
        $newObjects = [];
        $tagPrefixLength = strlen($this->newTagPrefix);
        foreach ($values as $key => $value) {
            $cleanValue = substr((string) $value, $tagPrefixLength);
            $valuePrefix = substr((string) $value, 0, $tagPrefixLength);
            if ($valuePrefix == $this->newTagPrefix) {
                $object = new $this->className();
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
