<?php

namespace SymfonySimpleSite\User\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use SymfonySimpleSite\User\UserBundle;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
       $treeBuilder = new TreeBuilder(UserBundle::getConfigName());
        //TODO: init config
        return $treeBuilder;
    }
}
