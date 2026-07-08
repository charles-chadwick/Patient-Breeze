<?php

use App\Enums\NoteType;
use Tests\TestCase;

uses(TestCase::class);

it('exposes all note type backing values', function (): void {
    expect(NoteType::values())->toBe([
        'General',
        'Clinical',
        'Administrative',
        'CarePlan',
    ]);
});

it('resolves a translated label for each case', function (): void {
    expect(NoteType::CarePlan->label())->toBe('Care Plan')
        ->and(NoteType::General->label())->toBe('General');
});
