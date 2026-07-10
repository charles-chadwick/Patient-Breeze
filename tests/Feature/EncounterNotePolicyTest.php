<?php

use App\Enums\UserRole;
use App\Models\EncounterNote;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

function staffUser(): User
{
    return User::factory()->withRole(UserRole::Doctor)->create();
}

it('lets the author sign an unsigned note but forbids a non-author', function () {
    $author = staffUser();
    $other = staffUser();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    expect($author->can('sign', $note))->toBeTrue()
        ->and($other->can('sign', $note))->toBeFalse();
});

it('lets a different user co-sign a signed note but not the signer', function () {
    $author = staffUser();
    $other = staffUser();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    expect($other->can('coSign', $note))->toBeTrue()
        ->and($author->can('coSign', $note))->toBeFalse();
});

it('forbids editing a signed note', function () {
    $author = staffUser();
    $unsigned = EncounterNote::factory()->for($author, 'author')->create();
    $signed = EncounterNote::factory()->for($author, 'author')->signed()->create();

    expect($author->can('update', $unsigned))->toBeTrue()
        ->and($author->can('update', $signed))->toBeFalse();
});
