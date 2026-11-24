<?php

namespace App\Enums;

enum PartnerTier: string
{
    case Title = 'title';
    case Gold = 'gold';
    case Silver = 'silver';
    case Event = 'event';
    case Community = 'community';

    /**
     * Label shown in the admin panel (used by Nova's Select field).
     */
    public function label(): string
    {
        return "{$this->name} Partner";
    }
}
