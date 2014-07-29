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
    protected $textProperty;

    public function __construct(EntityManager $em, $class, $textProperty)
    {
        $this->em = $em;
        $this->className = $class;
        $this->textProperty = $textProperty;
    }

    public function transform($entity)
    {
        if (null === $entity || '' === $entity) {
            return '';
        }

        // return the initial values as html encoded json

        $text = is_null($this->textProperty)
            ? (string) $entity
            : $entity->{'get' . $this->textProperty}();

        $data = array(
            'id' => $entity->getId(),
            'text' => $text
        );

        return htmlspecialchars(json_encode($data));
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
