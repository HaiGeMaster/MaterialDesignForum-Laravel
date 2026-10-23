<?php

namespace Tests\Unit;

use App\Support\RegistrationProfileDefaults;
use Tests\TestCase;

class RegistrationProfileDefaultsTest extends TestCase
{
    public function test_avatar_is_a_material_palette_svg_with_safe_initials(): void
    {
        $avatar = app(RegistrationProfileDefaults::class)->avatar('Ada Lovelace');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $avatar);
        $this->assertLessThanOrEqual(2000, strlen($avatar));
        $svg = base64_decode(substr($avatar, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($svg);
        $this->assertStringContainsString('AL</text>', $svg);

        $unsafeAvatar = app(RegistrationProfileDefaults::class)->avatar('<script>alert(1)</script>');
        $unsafeSvg = base64_decode(substr($unsafeAvatar, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($unsafeSvg);
        $this->assertStringNotContainsString('<script>', $unsafeSvg);
    }

    public function test_cover_is_chosen_from_the_material_asset_directory(): void
    {
        $cover = app(RegistrationProfileDefaults::class)->cover();

        $this->assertStringStartsWith('/assets/material/', $cover);
        $this->assertFileExists(public_path(ltrim($cover, '/')));
    }
}
