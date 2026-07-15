<?php

use App\Models\Media;
use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Attach an avatar to a patient and return the resulting media row.
 */
function attachAvatar(Patient $patient): Media
{
    return $patient->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');
}

it('uses the application media model so media activity is logged', function (): void {
    expect(config('media-library.media_model'))->toBe(Media::class);
});

it('keeps the file on disk while media is only soft deleted', function (): void {
    Storage::fake('public');
    $media = attachAvatar(Patient::factory()->create());
    $path = $media->getPathRelativeToRoot();

    $media->delete();

    expect(Media::withTrashed()->find($media->id)->trashed())->toBeTrue()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('retains recently trashed media when pruning', function (): void {
    Storage::fake('public');
    $media = attachAvatar(Patient::factory()->create());
    $path = $media->getPathRelativeToRoot();

    $media->delete();
    $this->artisan('model:prune', ['--model' => [Media::class]])->assertSuccessful();

    expect(Media::withTrashed()->find($media->id))->not->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('removes the row and the file once trashed media ages past the retention window', function (): void {
    Storage::fake('public');
    $media = attachAvatar(Patient::factory()->create());
    $path = $media->getPathRelativeToRoot();

    $media->delete();
    $media->forceFill(['deleted_at' => now()->subDays(Media::PRUNE_TRASHED_AFTER_DAYS + 1)])->saveQuietly();

    $this->artisan('model:prune', ['--model' => [Media::class]])->assertSuccessful();

    expect(Media::withTrashed()->find($media->id))->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('leaves live media untouched when pruning', function (): void {
    Storage::fake('public');
    $media = attachAvatar(Patient::factory()->create());
    $path = $media->getPathRelativeToRoot();

    $this->artisan('model:prune', ['--model' => [Media::class]])->assertSuccessful();

    expect(Media::find($media->id))->not->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});
