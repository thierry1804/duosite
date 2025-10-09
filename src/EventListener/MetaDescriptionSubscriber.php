<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

class MetaDescriptionSubscriber implements EventSubscriberInterface
{
    private $defaultDescriptions = [
        'default' => 'Duo Import MDG : votre partenaire expert pour l\'importation depuis la Chine vers Madagascar. Services de sourcing, contrôle qualité et logistique intégrée.',
        'home' => 'Duo Import MDG : votre partenaire de confiance pour l\'importation depuis la Chine vers Madagascar. Solutions complètes de sourcing et logistique.',
        'services' => 'Découvrez nos services d\'importation Chine-Madagascar : recherche de fournisseurs, contrôle qualité, gestion logistique et accompagnement personnalisé.',
        'about' => 'Duo Import MDG, votre expert en importation Chine-Madagascar depuis plus de 10 ans. Une équipe professionnelle à votre service.',
        'contact' => 'Contactez Duo Import MDG pour vos projets d\'importation Chine-Madagascar. Consultation gratuite et accompagnement personnalisé.',
    ];

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
            
            // Détermine la description appropriée
            $description = $this->getDescriptionForRoute($request);
            
            // Crée la meta description
            $metaDescription = sprintf(
                '<meta name="description" content="%s" />',
                htmlspecialchars($description)
            );
            
            // Remplace ou ajoute la meta description
            if (preg_match('/<meta[^>]*name="description"[^>]*>/i', $content)) {
                $content = preg_replace(
                    '/<meta[^>]*name="description"[^>]*>/i',
                    $metaDescription,
                    $content
                );
            } else {
                $content = preg_replace(
                    '/<head>/i',
                    '<head>' . $metaDescription,
                    $content,
                    1
                );
            }
            
            $response->setContent($content);
        }
    }

    private function getDescriptionForRoute(Request $request): string
    {
        $routeName = $request->get('_route');
        
        // Si une description spécifique existe pour cette route, l'utiliser
        if (isset($this->defaultDescriptions[$routeName])) {
            return $this->defaultDescriptions[$routeName];
        }
        
        // Sinon, utiliser la description par défaut
        return $this->defaultDescriptions['default'];
    }
} 