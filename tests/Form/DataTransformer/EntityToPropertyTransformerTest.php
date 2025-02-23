<?php

namespace Nspyke\Select2EntityBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use Nspyke\Select2EntityBundle\Form\TestEntity;
use PHPUnit\Framework\TestCase;

class EntityToPropertyTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManager::class);

        $this->transformer = new EntityToPropertyTransformer(
            $this->objectManagerMock,
            TestEntity::class,
            primaryKey: 'id',
        );
    }

    public function testTransformsEntityToProperty(): void
    {
        $entity = new TestEntity(name: 'Test Entity');
        $result = $this->transformer->transform($entity);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testTransformsNullEntityToNullProperty(): void
    {
        $result = $this->transformer->transform(null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testTransformsEmptyEntityToDefaultProperty()
    {
        $entity = new TestEntity(name: 'Test Entity');
        $result = $this->transformer->transform($entity);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testTransformsEntityWithSpecialCharacters(): void
    {
        $entity = new TestEntity(name: 'special@#%&*');
        $result = $this->transformer->transform($entity);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testTransformsEntityWithLongString(): void
    {
        $entity = new TestEntity(name: str_repeat('a', 1000));
        $result = $this->transformer->transform($entity);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
