<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Doctor = 'Doctor';
    case Nurse = 'Nurse';
    case MedicalAssistant = 'Medical Assistant';
    case Staff = 'Staff';

    /**
     * The resources guarded by CRUD permissions across the application.
     *
     * @var list<string>
     */
    private const RESOURCES = [
        'patients',
        'users',
        'appointments',
        'contacts',
        'documents',
        'discussions',
    ];

    /**
     * Every permission the application recognises (view/create/update/delete per resource).
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::RESOURCES as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $ability) {
                $permissions[] = "{$ability}_{$resource}";
            }
        }

        return $permissions;
    }

    /**
     * The permissions granted to this role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => self::allPermissions(),
            self::Doctor => [
                ...$this->abilitiesFor('patients', ['view', 'create', 'update']),
                ...$this->abilitiesFor('appointments', ['view', 'create', 'update', 'delete']),
                ...$this->abilitiesFor('contacts', ['view', 'create', 'update', 'delete']),
                ...$this->abilitiesFor('documents', ['view', 'create', 'update', 'delete']),
                ...$this->abilitiesFor('discussions', ['view', 'create', 'update', 'delete']),
            ],
            self::Nurse, self::MedicalAssistant => [
                ...$this->abilitiesFor('patients', ['view', 'update']),
                ...$this->abilitiesFor('appointments', ['view', 'create', 'update']),
                ...$this->abilitiesFor('contacts', ['view', 'create', 'update']),
                ...$this->abilitiesFor('documents', ['view', 'create', 'update']),
                ...$this->abilitiesFor('discussions', ['view', 'create', 'update']),
            ],
            self::Staff => [
                ...$this->abilitiesFor('patients', ['view']),
                ...$this->abilitiesFor('appointments', ['view', 'create', 'update']),
                ...$this->abilitiesFor('contacts', ['view', 'create', 'update']),
                ...$this->abilitiesFor('documents', ['view']),
                ...$this->abilitiesFor('discussions', ['view', 'create', 'update']),
            ],
        };
    }

    /**
     * Expand a resource and a set of abilities into permission names.
     *
     * @param  list<string>  $abilities
     * @return list<string>
     */
    private function abilitiesFor(string $resource, array $abilities): array
    {
        return array_map(fn (string $ability): string => "{$ability}_{$resource}", $abilities);
    }
}
