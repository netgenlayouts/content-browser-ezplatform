<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserEzPlatformBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;
use function array_keys;
use function file_get_contents;
use function in_array;

final class NetgenContentBrowserEzPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');

        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        if (in_array('NetgenTagsBundle', $activatedBundles, true)) {
            $loader->load('eztags/services.yaml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('default_settings.yaml');

        $this->doPrepend($container, 'config.yaml', 'netgen_content_browser');
        $this->doPrepend($container, 'image.yaml', 'ezpublish');

        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        if (in_array('NetgenTagsBundle', $activatedBundles, true)) {
            $this->doPrepend($container, 'eztags/config.yaml', 'netgen_content_browser');
        }
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    private function doPrepend(ContainerBuilder $container, string $fileName, string $configName): void
    {
        $configFile = __DIR__ . '/../Resources/config/' . $fileName;
        $config = Yaml::parse((string) file_get_contents($configFile));
        $container->prependExtensionConfig($configName, $config);
        $container->addResource(new FileResource($configFile));
    }
}
