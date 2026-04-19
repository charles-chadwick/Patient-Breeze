<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AssignSimpsonsAvatar
{
    /** @var array<string, array{file: string, url: string}> */
    public static array $avatars = [
        'Marge Simpson' => ['file' => 'marge-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/0b/Marge_Simpson.png'],
        'Bart Simpson' => ['file' => 'bart-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/a/aa/Bart_Simpson_200px.png'],
        'Lisa Simpson' => ['file' => 'lisa-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/e/ec/Lisa_Simpson.png'],
        'Abraham Simpson' => ['file' => 'abraham-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/Abe_Simpson.png'],
        'Ned Flanders' => ['file' => 'ned-flanders.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/84/Ned_Flanders.png'],
        'Waylon Smithers' => ['file' => 'waylon-smithers.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/86/Waylon_Smithers_1.png'],
        'Barney Gumble' => ['file' => 'barney-gumble.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/d/de/Barney_Gumble.png'],
        'Moe Szyslak' => ['file' => 'moe-szyslak.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/80/Moe_Szyslak.png'],
        'Ralph Wiggum' => ['file' => 'ralph-wiggum.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/1/14/Ralph_Wiggum.png'],
        'Milhouse Houten' => ['file' => 'milhouse-houten.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/1/11/Milhouse_Van_Houten.png'],
        'Nelson Muntz' => ['file' => 'nelson-muntz.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c6/Nelson_Muntz.PNG'],
        'Robert Terwilliger' => ['file' => 'robert-terwilliger.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c8/C-bob.png'],
        'Troy McClure' => ['file' => 'troy-mcclure.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/6/6c/Troymcclure.png'],
        'Herschel Krustofsky' => ['file' => 'herschel-krustofsky.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/5/5a/Krustytheclown.png'],
        'Fat Tony' => ['file' => 'fat-tony.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/FatTony.png'],
        'Patty Bouvier' => ['file' => 'patty-bouvier.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/f/f8/Patty_Bouvier.png'],
        'Selma Bouvier' => ['file' => 'selma-bouvier.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/f/f8/Patty_Bouvier.png'],
        'Frank Grimes' => ['file' => 'frank-grimes.jpg', 'url' => 'https://upload.wikimedia.org/wikipedia/en/e/e5/Frank_Grimes_%28holmes%29.jpeg'],
    ];

    public function execute(User $user): bool
    {
        $key = "{$user->first_name} {$user->last_name}";

        if (! isset(self::$avatars[$key])) {
            return false;
        }

        if ($user->relationLoaded('media') && $user->getFirstMedia('avatar')?->name === $key) {
            return false;
        }

        $source = $this->resolveSource(self::$avatars[$key]);

        if ($source === null) {
            return false;
        }

        $user->clearMediaCollection('avatar');

        try {
            $user->addMedia($source['path'])
                ->usingName($key)
                ->preservingOriginal()
                ->toMediaCollection('avatar');
        } catch (FileDoesNotExist|FileIsTooBig $e) {
        } finally {
            if ($source['temporary']) {
                @unlink($source['path']);
            }
        }

        return true;
    }

    /**
     * @param  array{file: string, url: string}  $config
     * @return array{path: string, temporary: bool}|null
     */
    private function resolveSource(array $config): ?array
    {
        $cached = database_path("data/avatars/{$config['file']}");

        if (file_exists($cached)) {
            return ['path' => $cached, 'temporary' => false];
        }

        sleep(3);

        $response = Http::withUserAgent('Mozilla/5.0 (compatible; Laravel)')
            ->get($config['url']);

        if (! $response->successful()) {
            return null;
        }

        $ext = pathinfo(parse_url($config['url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
        $tmp = tempnam(sys_get_temp_dir(), 'simpsons_').'.'.$ext;
        file_put_contents($tmp, $response->body());

        return ['path' => $tmp, 'temporary' => true];
    }
}
