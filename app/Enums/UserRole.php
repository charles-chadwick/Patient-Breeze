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
     * Resources guarded by permissions, in the order permissions are generated.
     *
     * @var list<string>
     */
    private const RESOURCES = ['patients', 'appointments', 'discussions', 'documents', 'contacts', 'notes', 'encounter_notes', 'users'];

    /**
     * Actions available for every guarded resource.
     *
     * @var list<string>
     */
    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }

    /**
     * Every permission the application recognises: one per resource/action pair.
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = $action.'_'.$resource;
            }
        }

        return $permissions;
    }

    /**
     * Permissions granted to this role, flattened from its resource grants.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $permissions = [];

        foreach ($this->grants() as $resource => $actions) {
            foreach ($actions as $action) {
                $permissions[] = $action.'_'.$resource;
            }
        }

        return $permissions;
    }

    /**
     * Resource => allowed actions for this role. Only the Super Admin manages users.
     *
     * @return array<string, list<string>>
     */
    private function grants(): array
    {
        return match ($this) {
            self::SuperAdmin => array_fill_keys(self::RESOURCES, self::ACTIONS),
            self::Doctor => [
                'patients' => ['view', 'create', 'update'],
                'appointments' => ['view', 'create', 'update', 'delete'],
                'discussions' => ['view', 'create', 'update', 'delete'],
                'documents' => ['view', 'create', 'update', 'delete'],
                'contacts' => ['view', 'create', 'update', 'delete'],
                'notes' => ['view', 'create', 'update', 'delete'],
                'encounter_notes' => ['view', 'create', 'update', 'delete'],
            ],
            self::Nurse, self::MedicalAssistant => [
                'patients' => ['view', 'update'],
                'appointments' => ['view', 'create', 'update'],
                'discussions' => ['view', 'create', 'update'],
                'documents' => ['view', 'create', 'update'],
                'contacts' => ['view', 'create', 'update'],
                'notes' => ['view', 'create', 'update'],
                'encounter_notes' => ['view', 'create', 'update'],
            ],
            self::Staff => [
                'patients' => ['view'],
                'appointments' => ['view', 'create', 'update'],
                'discussions' => ['view', 'create', 'update'],
                'documents' => ['view', 'create', 'update'],
                'contacts' => ['view', 'create', 'update'],
                'notes' => ['view', 'create', 'update'],
                'encounter_notes' => ['view', 'create', 'update'],
            ],
        };
    }
}
