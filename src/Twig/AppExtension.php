<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('autolink', [$this, 'autolinkFilter']),
        ];
    }

    /**
     * Convertit les URLs en liens cliquables sécurisés
     */
    public function autolinkFilter(string $text): string
    {
        // Regex plus robuste pour détecter les URLs
        $pattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        
        // Remplacer les URLs par des liens avec attributs de sécurité
        return preg_replace_callback($pattern, function($matches) {
            $url = $matches[0];
            
            // Ajouter http:// si l'URL commence par www.
            if (!preg_match('~^https?://~i', $url)) {
                $url = 'http://' . $url;
            }
            
            // Échapper l'URL pour éviter les injections XSS
            $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $displayUrl = htmlspecialchars($matches[0], ENT_QUOTES, 'UTF-8');
            
            // Créer le lien avec des attributs de sécurité
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer nofollow" class="external-link">%s</a>',
                $escapedUrl,
                $displayUrl
            );
        }, $text);
    }
} 