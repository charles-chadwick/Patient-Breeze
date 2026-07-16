<?php

namespace App\Enums;

enum SubscriberRelationship: string
{
    case Self = 'Self';
    case Spouse = 'Spouse';
    case Child = 'Child';
    case Other = 'Other';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.subscriber_relationship.'.$this->value);
    }

    /**
     * Whether the patient is the policy's own subscriber, so the subscriber name
     * defaults to the patient rather than needing a separate person.
     */
    public function isSelf(): bool
    {
        return $this === self::Self;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
