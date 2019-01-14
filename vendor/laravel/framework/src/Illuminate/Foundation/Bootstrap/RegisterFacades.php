<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Contracts\Foundation\Application;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */

    //可以看出来，bootstrap做了一下几件事：
    //清除了Facade中的缓存
    //设置Facade的Ioc容器
    //获得我们前面讲的config文件夹里面app文件aliases别名映射数组
    //使用aliases实例化初始化AliasLoader
    //调用AliasLoader->register()
    public function bootstrap(Application $app)
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
