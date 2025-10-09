<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExampleTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}

/**
 * Exemple de test fonctionnel pour les contrôleurs
 * Décommentez et adaptez ce code lorsque vous aurez installé le package symfony/test-pack
 */
/*
class HomeControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Duo Import MDG');
    }
}
*/ 