<?php

use App\Providers\AppServiceProvider;
use Laravolt\Avatar\ServiceProvider as AvatarServiceProvider;
use SocialiteProviders\Manager\ServiceProvider as SocialiteProvidersServiceProvider;

return [
    AppServiceProvider::class,
    AvatarServiceProvider::class,
    SocialiteProvidersServiceProvider::class,
];
