<?php

namespace Liuggio\StatsDClientBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds tagged data_collector services to profiler service
 * strongly inspired by Symfony ProfilerPass
 * @author <liuggio@gmail.com>
 */
class CollectorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('liuggio_stats_d_client.collector.service')) {
            return;
        }

        $serviceDefinition = $container->getDefinition('liuggio_stats_d_client.collector.service');
        $collectorsEnabled = $container->getParameter('liuggio_stats_d_client.collectors');
        $collectorIsEnabled = $container->getParameter('liuggio_stats_d_client.enable_collector');

        if ($collectorIsEnabled && null !== $collectorsEnabled && is_array($collectorsEnabled) && count($collectorsEnabled) > 0) {

            foreach ($container->findTaggedServiceIds('stats_d_collector') as $id => $attributes) {
                //only if there's on the parameter means that is enable
                if (!isset($collectorsEnabled[$id])) {
                    continue;
                }

                $collectorReference = new Reference($id);

                // adding to this collector the the collection
                $serviceDefinition->addMethodCall('add', array($collectorReference));

                $collectorDefinition = $container->getDefinition($id);
                $collectorDefinition->addMethodCall('setStatsDataKey', array($collectorsEnabled[$id]));

                // if is doctrine.dbal
                // we need to attach also the collector to the dbal sql logger chain
                if ($id === 'liuggio_stats_d_client.collector.dbal') {
                    $chainLogger = $container->getDefinition('doctrine.dbal.logger.chain');
                    if (null !== $chainLogger) {
                        $chainLogger->addMethodCall('addLogger', array($collectorReference));
                    }
                } elseif ($id === 'liuggio_stats_d_client.collector.solarium') {
                    foreach ($container->getDefinitions() as $definitionId => $definition) {
                        if (!preg_match('/^solarium.client.[^.]+$/', $definitionId)) {
                            continue;
                        }

                        $definition
                            ->addMethodCall(
                                'registerPlugin',
                                array('liuggio_stats_d_client', $collectorReference)
                            )
                        ;
                    }
                }
            }
        }
    }
}
