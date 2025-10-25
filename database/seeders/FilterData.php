<?php

/** @noinspection PhpUnused */

namespace Database\Seeders;

class FilterData
{
    public const BAD_WORDS = [
        'Titty',
        'Boob',
        'Fuck',
        'Shit',
        'Vagina',
        'Dick',
        'Cock',
        'Damn',
        'Dick',
        'Bitch',
        'Balls',
        'Nip',
        'Testicle',
        'Poopy',
        'Elon',
        'Fascist',
        'Hepatitis',
        'Slut',
        'Jacker',
        'Abradolf',
        'Hitler',
        'Butt',
        'Cunt',
        'Retard',
        'Tortured'
    ];

    public static function hasBadWords(string $string): bool
    {
        foreach (self::BAD_WORDS as $bad_word) {
            if (stripos($string, $bad_word) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function censor(string $string): string
    {
        foreach (self::BAD_WORDS as $bad_word) {
            $string = str_replace($bad_word, str_repeat('*', strlen($bad_word)), $string);
        }

        return $string;
    }
}
