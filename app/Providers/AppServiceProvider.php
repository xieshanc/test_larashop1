<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Yansongda\Pay\Pay;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 注入名为 alipay 的单例对象
        $this->app->singleton('alipay', function () {
            $config = config('pay.alipay');
            // $config['notify_url'] = route('payment.alipay.notify');
            $config['notify_url'] = 'http://requestbin.net/r/1ceea231';
            $config['return_url'] = route('payment.alipay.return');
            if (app()->environment() !== 'production') {
                $config['mode'] = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            return Pay::alipay($config);
        });

        // 注入名为 wechat_pay 的单例对象
        $this->app->singleton('wechat_pay', function () {
            $config = config('pay.wechat');
            if (app()->environment() !== 'production') {
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            return Pay::wechat($config);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
    }
}
