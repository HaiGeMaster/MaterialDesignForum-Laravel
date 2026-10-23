<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileInfo;

class RegistrationProfileDefaults
{
    public function avatar(string $username): string
    {
        $safeName = str_replace(['<', '>', '&', '"', "'"], ' ', $username);
        $safeName = preg_replace('/[\x00-\x1F\x7F]/', ' ', $safeName) ?? '';
        $safeName = trim(preg_replace('/\s+/', ' ', $safeName) ?? '');

        if ($safeName === '') {
            $safeName = '?';
        }

        $svg = app('avatar')
            ->create(Str::upper($safeName))
            ->setChars(2)
            ->toSvg();

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function cover(): string
    {
        $directory = public_path('assets/material');
        $images = array_values(array_filter(
            File::files($directory),
            static fn (SplFileInfo $file): bool => in_array(
                strtolower($file->getExtension()),
                ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                true,
            ),
        ));

        if ($images === []) {
            throw new RuntimeException('No default cover images found in public/assets/material.');
        }

        $image = $images[random_int(0, count($images) - 1)];

        return '/assets/material/'.rawurlencode($image->getFilename());
        // return asset('assets/material/'.rawurlencode($image->getFilename()));
    }
}
