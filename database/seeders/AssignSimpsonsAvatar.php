<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AssignSimpsonsAvatar
{
    /** @var array<string, array{file: string, url: string}> */
    public static array $avatars = [
        'Homer Simpson' => ['file' => 'homer-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/02/Homer_Simpson_2006.png'],
        'Charles Burns' => ['file' => 'charles-burns.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/5/56/Mr_Burns.png'],
        'Clancy Wiggum' => ['file' => 'clancy-wiggum.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Chief_Wiggum.png'],
        'Edna Krabappel' => ['file' => 'edna-krabappel.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/7/76/Edna_Krabappel.png'],
        'Julius Hibbert' => ['file' => 'julius-hibbert.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/thumb/9/98/Dr._Hibbert.svg/250px-Dr._Hibbert.svg.png'],
        'Nick Riviera' => ['file' => 'nick-riviera.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c6/Dr_Nick.png'],
        'Timothy Lovejoy' => ['file' => 'timothy-lovejoy.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/7/7d/Rev_Lovejoy.png'],
        'Seymour Skinner' => ['file' => 'seymour-skinner.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3a/Seymour_Skinner.png'],
        'Kent Brockman' => ['file' => 'kent-brockman.jpg', 'url' => 'https://upload.wikimedia.org/wikipedia/en/9/9d/Kent_Brockman.jpg'],
        'Lionel Hutz' => ['file' => 'lionel-hutz.jpg', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3f/Lionel_Hutz.jpg'],
        'Marge Simpson' => ['file' => 'marge-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/0b/Marge_Simpson.png'],
        'Bart Simpson' => ['file' => 'bart-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/a/aa/Bart_Simpson_200px.png'],
        'Lisa Simpson' => ['file' => 'lisa-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/e/ec/Lisa_Simpson.png'],
        'Abraham Simpson' => ['file' => 'abraham-simpson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/Abe_Simpson.png'],
        'Ned Flanders' => ['file' => 'ned-flanders.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/84/Ned_Flanders.png'],
        'Maude Flanders' => ['file' => 'maude-flanders.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/04/Maude_Flanders.png'],
        'Rod Flanders' => ['file' => 'rod-flanders.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/a/a1/Rod_Flanders.png'],
        'Todd Flanders' => ['file' => 'todd-flanders.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/9/96/Todd_Flanders.png'],
        'Waylon Smithers' => ['file' => 'waylon-smithers.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/86/Waylon_Smithers_1.png'],
        'Barney Gumble' => ['file' => 'barney-gumble.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/d/de/Barney_Gumble.png'],
        'Moe Szyslak' => ['file' => 'moe-szyslak.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/80/Moe_Szyslak.png'],
        'Lenny Leonard' => ['file' => 'lenny-leonard.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/a/a8/Lenny_Leonard.png'],
        'Carl Carlson' => ['file' => 'carl-carlson.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/04/Carl_Carlson.png'],
        'Ralph Wiggum' => ['file' => 'ralph-wiggum.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/1/14/Ralph_Wiggum.png'],
        'Apu Nahasapeemapetilon' => ['file' => 'apu-nahasapeemapetilon.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/0/0b/Apu_Nahasapeemapetilon.png'],
        'Milhouse Houten' => ['file' => 'milhouse-houten.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/1/11/Milhouse_Van_Houten.png'],
        'Luann Houten' => ['file' => 'luann-houten.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/a/a2/Luann_Van_Houten.png'],
        'Kirk Houten' => ['file' => 'kirk-houten.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/82/Kirk_Van_Houten.png'],
        'Nelson Muntz' => ['file' => 'nelson-muntz.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c6/Nelson_Muntz.PNG'],
        'Agnes Skinner' => ['file' => 'agnes-skinner.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/d/d5/Agnes_Skinner.png'],
        'Patty Bouvier' => ['file' => 'patty-bouvier.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/f/f8/Patty_Bouvier.png'],
        'Selma Bouvier' => ['file' => 'selma-bouvier.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/f/f8/Patty_Bouvier.png'],
        'Robert Terwilliger' => ['file' => 'robert-terwilliger.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c8/C-bob.png'],
        'Troy McClure' => ['file' => 'troy-mcclure.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/6/6c/Troymcclure.png'],
        'Hans Moleman' => ['file' => 'hans-moleman.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/5/5c/Hans_Moleman.png'],
        'Otto Mann' => ['file' => 'otto-mann.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/6/63/Otto_Mann.png'],
        'Helen Lovejoy' => ['file' => 'helen-lovejoy.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/8/8f/Helen_Lovejoy.png'],
        'Jasper Beardly' => ['file' => 'jasper-beardly.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/f/f6/Jasper_Beardly.png'],
        'Rainier Wolfcastle' => ['file' => 'rainier-wolfcastle.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/e/ec/Rainier_Wolfcastle.png'],
        'Cletus Spuckler' => ['file' => 'cletus-spuckler.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/Cletus_Spuckler.png'],
        'Brandine Spuckler' => ['file' => 'brandine-spuckler.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/Cletus_Spuckler.png'],
        'Herschel Krustofsky' => ['file' => 'herschel-krustofsky.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/5/5a/Krustytheclown.png'],
        'Fat Tony' => ['file' => 'fat-tony.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/FatTony.png'],
        'Snake Jailbird' => ['file' => 'snake-jailbird.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/4/46/Snake_Jailbird.png'],
        'Artie Ziff' => ['file' => 'artie-ziff.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/4/47/Artie_Ziff.png'],
        'Frank Grimes' => ['file' => 'frank-grimes.jpg', 'url' => 'https://upload.wikimedia.org/wikipedia/en/e/e5/Frank_Grimes_%28holmes%29.jpeg'],
        'Lyle Lanley' => ['file' => 'lyle-lanley.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/c/c6/Lyle_Lanley.png'],
        'Herb Powell' => ['file' => 'herb-powell.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/3/3c/Herb_Powell.png'],
        'Mindy Simmons' => ['file' => 'mindy-simmons.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/4/44/Mindy_Simmons.png'],
        'Lurleen Lumpkin' => ['file' => 'lurleen-lumpkin.png', 'url' => 'https://upload.wikimedia.org/wikipedia/en/1/18/Lurleen_Lumpkin.png'],
    ];

    public function execute(User|Patient $model): bool
    {
        $key = "{$model->first_name} {$model->last_name}";

        if (! isset(self::$avatars[$key])) {
            return false;
        }

        if ($model->relationLoaded('media') && $model->getFirstMedia('avatar')?->name === $key) {
            return false;
        }

        $source = $this->resolveSource(self::$avatars[$key]);

        if ($source === null) {
            return false;
        }

        $model->clearMediaCollection('avatar');

        try {
            $model->addMedia($source['path'])
                ->usingName($key)
                ->preservingOriginal()
                ->toMediaCollection('avatar');
        } catch (FileDoesNotExist|FileIsTooBig) {
            // Non-critical; continue seeding other users
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

        file_put_contents($cached, $response->body());

        return ['path' => $cached, 'temporary' => false];
    }
}
