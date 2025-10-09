<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PerformanceListener implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        
        // Add cache control headers for better caching
        if (!$response->headers->has('Cache-Control') && 
            $request->getMethod() === 'GET' && 
            !$request->getSession()->isStarted()) {
            
            // Set cache control headers for static assets
            $path = $request->getPathInfo();
            if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|webp|woff|woff2|ttf|svg)$/', $path)) {
                $response->headers->set('Cache-Control', 'public, max-age=31536000'); // 1 year
            } else {
                // Set reasonable cache for HTML pages
                $response->headers->set('Cache-Control', 'public, max-age=3600'); // 1 hour
            }
        }
        
        // Add content encoding headers
        if (!$response->headers->has('Content-Encoding') && $response->getContent()) {
            $acceptEncoding = $request->headers->get('Accept-Encoding');
            if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode')) {
                $content = $response->getContent();
                $compressed = gzencode($content, 9);
                
                if ($compressed !== false && strlen($compressed) < strlen($content)) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Vary', 'Accept-Encoding');
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10], // Low priority to run after other listeners
        ];
    }
} 