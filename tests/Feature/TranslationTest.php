<?php

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\BloodType;
use App\Enums\ContactType;
use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Enums\DocumentType;
use App\Enums\DoseForm;
use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('shares the active locale with the frontend', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('locale', app()->getLocale()));
});

it('resolves a translated, non-key label for every enum case', function (string $enum, string $group): void {
    foreach ($enum::cases() as $case) {
        $key = "enums.{$group}.{$case->value}";

        expect(Lang::has($key))->toBeTrue("Missing translation key: {$key}");
        expect($case->label())->toBe(__($key))->not->toBe($key);
    }
})->with([
    [UserRole::class, 'user_role'],
    [AppointmentRole::class, 'appointment_role'],
    [AppointmentStatus::class, 'appointment_status'],
    [BloodType::class, 'blood_type'],
    [ContactType::class, 'contact_type'],
    [DiscussionPostStatus::class, 'discussion_post_status'],
    [DiscussionType::class, 'discussion_type'],
    [DocumentType::class, 'document_type'],
    [DoseForm::class, 'dose_form'],
    [GenderAtBirth::class, 'gender_at_birth'],
    [GenderIdentity::class, 'gender_identity'],
]);

it('flashes a translated success message when a user is created', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $this->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Doctor->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHas('success', __('flash.users.created'));
});

/**
 * Guard: every statically-referenced translation key across all Vue files
 * must exist in lang/en so nothing silently renders as a raw key.
 *
 * @return array<int, string>
 */
function referencedTranslationKeys(): array
{
    $files = array_merge(
        glob(resource_path('js/**/*.vue'), GLOB_BRACE) ?: [],
        rglob(resource_path('js'), 'vue'),
    );

    $keys = [];

    foreach (array_unique($files) as $file) {
        $contents = file_get_contents($file);

        // Match $t('key') and trans('key') with a plain single-quoted literal.
        // Concatenated/dynamic keys (e.g. 'enums.user_role.' + name) are skipped
        // here and covered by the per-enum test above.
        preg_match_all("/(?:\\\$t|trans)\('([a-z0-9_.]+)'\s*[),]/i", $contents, $matches);

        foreach ($matches[1] as $key) {
            $keys[$key] = $key;
        }
    }

    return array_values($keys);
}

/**
 * @return array<int, string>
 */
function rglob(string $directory, string $extension): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    $matches = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === $extension) {
            $matches[] = $file->getPathname();
        }
    }

    return $matches;
}

it('defines every translation key referenced in the Vue components', function (): void {
    $keys = referencedTranslationKeys();

    expect($keys)->not->toBeEmpty();

    $missing = array_filter($keys, fn (string $key): bool => ! Lang::has($key));

    expect($missing)->toBe([], 'Missing translation keys: '.implode(', ', $missing));
});
