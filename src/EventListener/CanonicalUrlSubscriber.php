<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CanonicalUrlSubscriber implements EventSubscriberInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Vérifie si la réponse est HTML
        if ($response->headers->get('Content-Type') && strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            $content = $response->getContent();
            
            // Génère l'URL canonique
            $canonicalUrl = $request->getSchemeAndHttpHost() . $request->getRequestUri();
            
            // Crée la balise canonique
            $canonicalTag = sprintf(
                '<link rel="canonical" href="%s" />',
                htmlspecialchars($canonicalUrl)
            );
            
            // Insère la balise canonique dans le head
            $content = preg_replace(
                '/<head>/i',
                '<head>' . $canonicalTag,
                $content,
                1
            );
            
            $response->setContent($content);
        }
    }
} 