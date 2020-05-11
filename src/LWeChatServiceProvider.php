<?php

namespace Lany\LWeChat;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LWeChatServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('l_wechat', function ($app) {
            return new LWeChat($app['config']);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/l_wechat.php' => config_path('l_wechat.php'),
        ]);
        //$this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * 获取由提供者提供的服务。
     *
     * @return array
     */
    public function provides()
    {
        return [LWeChat::class];
    }
}
