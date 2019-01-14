<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));


require __DIR__.'/../vendor/autoload.php';


$app = require_once __DIR__.'/../bootstrap/app.php';


$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
    //这句是laravel通过全局$_SERVER数组构造一个Http请求的语句，接下来会调用Http的内核函数handle：
);

$response->send();

$kernel->terminate($request, $response);
//第一句就是我们前面博客说的composer的自动加载，
//接下来第二句获取laravel核心的Ioc容器，
//第三句“制造”出Http请求的内核，
//第四句是我们这里的关键，这句牵扯很大，laravel里面所有功能服务的注册加载，乃至Http请求的构造与传递都是这一句的功劳。