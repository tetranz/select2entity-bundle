<?php

namespace Nspyke\Select2EntityBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Nspyke\Select2EntityBundle\Form\TestEntity;
use Nspyke\Select2EntityBundle\Form\Type\Select2EntityType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\RouterInterface;

class Select2EntityTypeTest extends TypeTestCase
{
    private MockObject $managerRegistry;
    private MockObject $router;
    private array $config;

    public function testSubmit_singleValidValue(): void
    {
        $form = $this->factory->create(Select2EntityType::class, null, [
            'class' => TestEntity::class,
        ]);

        $formData = 1;
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
    }

    protected function getTypes(): array
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->atLeastOnce())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->atLeastOnce())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('from')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->any())
            ->method('getSingleResult')
            ->willReturn(new TestEntity());

        $this->config = [];

        $this->managerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        return array_merge(parent::getTypes(), [
            new Select2EntityType($this->managerRegistry, $this->router, $this->config),
        ]);
    }
}
