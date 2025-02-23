<?php

namespace Nspyke\Select2EntityBundle\Service;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class AutocompleteServiceTest extends TestCase
{
    private MockObject $formFactory;
    private MockObject $doctrine;
    private MockObject $form;
    private MockObject $formField;
    private MockObject $config;
    private MockObject $repo;
    private AutocompleteService $service;
    private MockObject $expr;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->service = new AutocompleteService($this->formFactory, $this->doctrine);

        $this->form = $this->createMock(Form::class);
        $this->formField = $this->createMock(Form::class);
        $this->config = $this->createMock(FormConfigInterface::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->expr = $this->createMock(Query\Expr::class);
    }

    public function testBasicSearchScenario(): void
    {
        $request = new Request(['field_name' => 'entity', 'q' => 'test', 'page' => 1]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->createMock(Query::class);

        // Setup test entity
        $entity = new class () {
            public function getId(): int
            {
                return 1;
            }

            public function getName(): string
            {
                return 'Test Entity';
            }
        };

        // Configure mocks
        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('get')
            ->with('entity')
            ->willReturn($this->formField);

        $this->formField->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('getOptions')
            ->willReturn([
                'class' => 'TestEntity',
                'property' => 'name',
                'primary_key' => 'id',
                'page_limit' => 10,
                'callback' => null,
            ]);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->repo->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($this->expr);

        $this->expr->expects($this->atLeast(1))
            ->method('count')
            ->willReturn(1);

        $query->expects($this->any())
            ->method('getSingleScalarResult')
            ->willReturn(1);
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([$entity]);

        $result = $this->service->getAutocompleteResults($request, 'TestType');

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('more', $result);
        $this->assertCount(1, $result['results']);
        $this->assertFalse($result['more']);
        $this->assertEquals(1, $result['results'][0]['id']);
        $this->assertEquals('Test Entity', $result['results'][0]['text']);
    }

    public function paginationScenario(): void
    {
        $request = new Request(['field_name' => 'entity', 'q' => 'test', 'page' => 2]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->createMock(Query::class);

        $entity = new class () {
            public function getId(): int
            {
                return 2;
            }

            public function getName(): string
            {
                return 'Test Entity 2';
            }
        };

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('get')
            ->with('entity')
            ->willReturn($this->formField);

        $this->formField->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('getOptions')
            ->willReturn([
                'class' => 'TestEntity',
                'property' => 'name',
                'primary_key' => 'id',
                'page_limit' => 10,
                'callback' => null,
            ]);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->repo->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

     $queryBuilder->expects($this->any())
         ->method('expr')
         ->willReturn($this->expr);

     $this->expr->expects($this->atLeast(1))
         ->method('count')
         ->willReturn(1);

     $query->expects($this->any())
         ->method('getSingleScalarResult')
         ->willReturn(1);
     $query->expects($this->any())
         ->method('getResult')
         ->willReturn([$entity]);

     $result = $this->service->getAutocompleteResults($request, 'TestType');

     $this->assertArrayHasKey('results', $result);
     $this->assertArrayHasKey('more', $result);
     $this->assertCount(1, $result['results']);
     $this->assertFalse($result['more']);
     $this->assertEquals(2, $result['results'][0]['id']);
     $this->assertEquals('Test Entity 2', $result['results'][0]['text']);
 }

    public function callbackScenario(): void
    {
        $request = new Request(['field_name' => 'entity', 'q' => 'test', 'page' => 1]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->createMock(Query::class);

        $entity = new class () {
            public function getId(): int
            {
                return 3;
            }

            public function getName(): string
            {
                return 'Test Entity 3';
            }
        };

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('get')
            ->with('entity')
            ->willReturn($this->formField);

        $this->formField->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('getOptions')
            ->willReturn([
                'class' => 'TestEntity',
                'property' => 'name',
                'primary_key' => 'id',
                'page_limit' => 10,
                'callback' => function ($queryBuilder, $query) {
                    $queryBuilder->andWhere('e.name LIKE :query')
                        ->setParameter('query', '%' . $query . '%');
                },
            ]);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->repo->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($this->expr);

        $this->expr->expects($this->atLeast(1))
            ->method('count')
            ->willReturn(1);

        $query->expects($this->any())
            ->method('getSingleScalarResult')
            ->willReturn(1);
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([$entity]);

        $result = $this->service->getAutocompleteResults($request, 'TestType');

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('more', $result);
        $this->assertCount(1, $result['results']);
        $this->assertFalse($result['more']);
        $this->assertEquals(3, $result['results'][0]['id']);
        $this->assertEquals('Test Entity 3', $result['results'][0]['text']);
    }

    public function emptyResultsScenario(): void
    {
        $request = new Request(['field_name' => 'entity', 'q' => 'nonexistent', 'page' => 1]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->createMock(Query::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('get')
            ->with('entity')
            ->willReturn($this->formField);

        $this->formField->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->config->expects($this->once())
            ->method('getOptions')
            ->willReturn([
                'class' => 'TestEntity',
                'property' => 'name',
                'primary_key' => 'id',
                'page_limit' => 10,
                'callback' => null,
            ]);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->repo->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($this->expr);

        $this->expr->expects($this->atLeast(1))
            ->method('count')
            ->willReturn(0);

        $query->expects($this->any())
            ->method('getSingleScalarResult')
            ->willReturn(0);
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([]);

        $result = $this->service->getAutocompleteResults($request, 'TestType');

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('more', $result);
        $this->assertCount(0, $result['results']);
        $this->assertFalse($result['more']);
    }
}
