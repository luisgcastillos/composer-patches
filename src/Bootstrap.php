<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Factories;
use Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool;

/**
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Factories\ConfigFactory
     */
    private $configFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory
     */
    private $loaderFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesApplierFactory
     */
    private $applierFactory;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Processor
     */
    private $repositoryProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool
     */
    private $loaderComponents;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\OutputStrategy
     */
    private $outputStrategy;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $appIO
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     * @param array $config
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $appIO,
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver = null,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy = null,
        $config = array()
    ) {
        $this->composer = $composer;
        $this->listResolver = $listResolver;
        $this->outputStrategy = $outputStrategy;
        $this->config = $config;

        $this->loaderComponents = new ComponentPool($composer, $appIO);

        $logger = new \Vaimo\ComposerPatches\Logger($appIO);
        
        $this->configFactory = new Factories\ConfigFactory($composer);
        $this->loaderFactory = new Factories\PatchesLoaderFactory($composer);
        $this->applierFactory = new Factories\PatchesApplierFactory($composer, $logger);
        
        $this->repositoryProcessor = new \Vaimo\ComposerPatches\Repository\Processor($logger);
    }

    public function applyPatches($devMode = false)
    {
        return $this->applyPatchesWithConfig(
            $this->configFactory->create(array($this->config)),
            $devMode
        );
    }

    public function stripPatches($devMode = false)
    {
        $configSources = array(
            $this->config,
            array(PluginConfig::PATCHER_SOURCES => false)
        );

        $this->applyPatchesWithConfig(
            $this->configFactory->create($configSources),
            $devMode
        );
    }
    
    private function applyPatchesWithConfig(PluginConfig $config, $devMode = false)
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $patchesLoader = $this->loaderFactory->create($this->loaderComponents, $config, $devMode);
        
        $patchesApplier = $this->applierFactory->create(
            $config,
            $this->listResolver,
            $this->outputStrategy
        );

        return $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }
}
