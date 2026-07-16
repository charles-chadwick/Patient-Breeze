<?php

namespace App\Enums;

enum OxygenDelivery: string
{
    case RoomAir = 'Room Air';
    case NasalCannula = 'Nasal Cannula';
    case FaceMask = 'Face Mask';
    case NonRebreather = 'Non-Rebreather';
    case HighFlow = 'High-Flow';
    case Ventilator = 'Ventilator';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.oxygen_delivery.'.$this->value);
    }

    /**
     * Whether the reading was taken on unassisted breathing — the default and
     * the case that needs no further explanation.
     */
    public function isRoomAir(): bool
    {
        return $this === self::RoomAir;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
