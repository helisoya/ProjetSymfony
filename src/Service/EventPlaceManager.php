<?php

namespace App\Service;


use App\Entity\Event;

readonly class EventPlaceManager
{
    public function __construct(
        )
    {
    }

    public function computeRemainingSeats (Event $event): int
    {
        return $event->getMaxParticipants() - $event->getParticipants()->count();
    }
}