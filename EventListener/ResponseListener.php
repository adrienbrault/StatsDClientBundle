<?php

namespace Liuggio\StatsDClientBundle\EventListener;

use AdrienBrault\StatsDCollector\StatsDDataCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author <liuggio@gmail.com>
 */
class ResponseListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse', -100),
        );
    }

    private $collector;

    public function __construct(
        
        StatsDDataCollector $collector
    ) {
        $this->collector = $collector;
    }

    /**
     * Handles the onKernelResponse event.
     *
     * @param FilterResponseEvent $event A FilterResponseEvent instance
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $statsData = $this->collector->collect();

    }
}
