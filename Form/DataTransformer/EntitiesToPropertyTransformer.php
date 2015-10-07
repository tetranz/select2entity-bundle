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
    protected $em;
    protected $className;
    protected $textProperty;

    public function __construct(EntityManager $em, $class, $textProperty)
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
    }

    /**
     * Transform initial entities as json with id and text
     *
     * @param mixed $entities
     * @return string
     */
    public function transform($entities)
    {
        if (count($entities) == 0) {
            return '';
        }

        // return an array of initial values as html encoded json
        $data = array();

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach($entities as $entity) {

            $text = is_null($this->textProperty)
                    ? (string) $entity
                    : $accessor->getValue($entity, $this->textProperty);

            $data[] = array(
                'id' => $accessor->getValue($entity, 'id'),
                'text' => $text
            );
        }

        return htmlspecialchars(json_encode($data));
    }

    /**
     * Transform csv list of ids to a collection of entities
     *
     * @param string $values as a CSV list
     * @return array|ArrayCollection|mixed
     */
    public function reverseTransform($values)
    {
        // remove the 'magic' non-blank value added in fields.html.twig
        $values = ltrim($values, 'x,');

        if (null === $values || '' === $values) {
            return new ArrayCollection();
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
