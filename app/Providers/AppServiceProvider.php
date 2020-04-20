<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Logger;
use Yansongda\Pay\Pay;
use Elasticsearch\ClientBuilder as ESClientBuilder;

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
            $config['notify_url'] = 'http://requestbin.net/r/1nrqqwy1';
            $config['return_url'] = route('payment.return');
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

        // 注册名为 es 的单例
        $this->app->singleton('es', function () {
            $builder = ESClientBuilder::create()->setHosts(config('database.elasticsearch.hosts'));
            if (app()->environment() === 'local') {
                $builder->setLogger(app('log')->driver());
            }
            return $builder->build();
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
        // 给视图里注入变量
        \View::composer(['products.index', 'products.show'], \App\Http\ViewComposers\CategoryTreeComposer::class);

        if (app()->environment('local')) {
            \DB::listen(function ($query) {
                \Log::info(Str::replaceArray('?', $query->bindings, $query->sql));
            });
        }
    }
}
