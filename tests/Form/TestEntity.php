<?php

namespace Nspyke\Select2EntityBundle\Form;

class TestEntity
{
    public function __construct(private int $id = 1, private string $name = 'Test Entity')
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
