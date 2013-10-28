<?php

namespace Liuggio\StatsDClientBundle\StatsCollector;

use Solarium\Core\Client\Client;
use Solarium\Core\Event\Events;
use Solarium\Core\Plugin\PluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Solarium\Core\Event\PreExecuteRequest;
use Solarium\Core\Event\PostExecuteRequest;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request as SolariumRequest;

class SolariumStatsCollector extends StatsCollector implements PluginInterface
{
    private $currentStartTime;

    /**
     * @var SolariumRequest
     */
    private $currentRequest;

    /**
     * @var Endpoint
     */
    private $currentEndpoint;

    /**
     * {@inheritdoc}
     */
    public function initPlugin($client, $options)
    {
        $dispatcher = $client->getEventDispatcher();
        $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'), 1000);
        $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'), -1000);
    }

    public function preExecuteRequest(PreExecuteRequest $event)
    {
        if (isset($this->currentRequest)) {
            $this->failCurrentRequest();
        }

        $this->currentRequest = $event->getRequest();
        $this->currentEndpoint = $event->getEndpoint();

        $this->currentStartTime = microtime(true);
    }

    public function postExecuteRequest(PostExecuteRequest $event)
    {
        $endTime = microtime(true) - $this->currentStartTime;

        if (!isset($this->currentRequest)) {
            throw new \RuntimeException('Request not set');
        }
        if ($this->currentRequest !== $event->getRequest()) {
            throw new \RuntimeException('Requests differ');
        }

        $key = sprintf(
            '%s.%s',
            $this->getStatsDataKey(),
            $this->currentEndpoint->getKey()
        );
        if (null === $this->getStatsdDataFactory()) {
            return;
        }
        $statData = $this->getStatsdDataFactory()->timing($key, $endTime);
        $this->addStatsData($statData);

        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function failCurrentRequest()
    {
        $this->currentRequest = null;
        $this->currentStartTime = null;
        $this->currentEndpoint = null;
    }

    public function setOptions($options, $overwrite = false) { }
    public function getOption($name) { }
    public function getOptions() { }
}
