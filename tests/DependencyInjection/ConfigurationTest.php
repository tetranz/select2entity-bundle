<?php

namespace Nspyke\Select2EntityBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void {
        $config = new Configuration();

        $this->assertInstanceOf(TreeBuilder::class, $config->getConfigTreeBuilder());
    }
}
