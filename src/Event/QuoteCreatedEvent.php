<?php

namespace App\Event;

use App\Entity\Quote;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché lorsqu'un nouveau devis est créé.
 */
class QuoteCreatedEvent extends Event
{
    public const NAME = 'quote.created';

    public function __construct(
        private Quote $quote
    ) {
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }
}
