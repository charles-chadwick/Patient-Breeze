<?php

namespace App\Enums;

enum SettingKey: string
{
    case Theme = 'Theme';
    case EmailNotifications = 'Email Notifications';
    case ItemsPerPage = 'Items Per Page';
    case ReceivesPortalMessages = 'Receive Portal Messages';

    public function label(): string
    {
        return __('enums.setting_key.'.$this->value);
    }

    /**
     * The allowed values a user may store for this setting.
     *
     * @return string[]
     */
    public function options(): array
    {
        return match ($this) {
            self::Theme => ['System', 'Light', 'Dark'],
            self::EmailNotifications, self::ReceivesPortalMessages => ToggleValue::values(),
            self::ItemsPerPage => ['10', '25', '50'],
        };
    }

    /**
     * The value applied when the user has not chosen one.
     */
    public function default(): string
    {
        return match ($this) {
            self::Theme => 'System',
            self::EmailNotifications => ToggleValue::Enabled->value,
            self::ItemsPerPage => '10',
            self::ReceivesPortalMessages => ToggleValue::Disabled->value,
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
