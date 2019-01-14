<?php

namespace Illuminate\Foundation;

class AliasLoader
{
    /**
     * The array of class aliases.
     *
     * @var array
     */
    protected $aliases;

    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * The namespace for all real-time facades.
     *
     * @var string
     */
    protected static $facadeNamespace = 'Facades\\';

    /**
     * The singleton instance of the loader.
     *
     * @var \Illuminate\Foundation\AliasLoader
     */
    protected static $instance;

    /**
     * Create a new AliasLoader instance.
     *
     * @param  array  $aliases
     * @return void
     */
    private function __construct($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Get or create the singleton alias loader instance.
     *
     * @param  array  $aliases
     * @return \Illuminate\Foundation\AliasLoader
     */
    public static function getInstance(array $aliases = [])
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
     *
     * @param  string  $alias
     * @return bool|null
     */
    public function load($alias)
    {
        //laravel5.4版本新出的功能叫做实时门面服务。
        //如果命名空间是以Facades\开头的，那么就会调用实时门面的功能，调用loadFacade函数：
        if (static::$facadeNamespace && strpos($alias, static::$facadeNamespace) === 0) {
            $this->loadFacade($alias);

            return true;
        }
        //这个函数的下面很好理解，就是class_alias利用别名映射数组将别名映射到真正的门面类中去.
        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }

    /**
     * Load a real-time facade for the given alias.
     *
     * @param  string  $alias
     * @return void
     */
    protected function loadFacade($alias)
    {
        //ensureFacadeExists函数负责自动生成门面类，loadFacade负责加载门面类：
        require $this->ensureFacadeExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
     *
     * @param  string  $alias
     * @return string
     */
    protected function ensureFacadeExists($alias)
    {
        //，laravel框架生成的门面类会放到stroge/framework/cache/文件夹下，名字以facade开头，以命名空间的哈希结尾
        //如果存在这个文件就会返回，否则就要利用file_put_contents生成这个文件，formatFacadeStub：
        //简单的说，对于Facades\App\Services\PaymentGateway，$replacements第一项是门面命名空间，将Facades\App\Services\PaymentGateway转为Facades/App/Services/PaymentGateway，取前面Facades/App/Services/，再转为命名空间Facades\App\Services\；
        //第二项是门面类名，PaymentGateway；
        //第三项是门面类的服务对象，App\Services\PaymentGateway，
        //用这些来替换门面的模板文件：
        /**
         *
         * <?php

            namespace DummyNamespace;

            use Illuminate\Support\Facades\Facade;


            class DummyClass extends Facade
            {

                protected static function getFacadeAccessor()
                {
                    return 'DummyTarget';
                }
            }
         *替換后的文件
         * <?php

            namespace Facades\App\Services\;

            use Illuminate\Support\Facades\Facade;


            class PaymentGateway extends Facade
            {

                protected static function getFacadeAccessor()
                {
                    return 'App\Services\PaymentGateway';
                }
            }

         *
         * 门面的原理就是这些，相对来说门面服务的原理比较简单，和自动加载相互配合使得代码更加简洁，希望大家可以更好的使用这些门面！
         **/
        if (file_exists($path = storage_path('framework/cache/facade-'.sha1($alias).'.php'))) {
            return $path;
        }

        file_put_contents($path, $this->formatFacadeStub(
            $alias, file_get_contents(__DIR__.'/stubs/facade.stub')
        ));

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
     *
     * @param  string  $alias
     * @param  string  $stub
     * @return string
     */
    protected function formatFacadeStub($alias, $stub)
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias),
            substr($alias, strlen(static::$facadeNamespace)),
        ];

        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
        );
    }

    /**
     * Add an alias to the loader.
     *
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;
    }

    /**
     * Register the loader on the auto-loader stack.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->registered) {
            $this->prependToLoaderStack();

            $this->registered = true;
        }
    }

    /**
     * Prepend the load method to the auto-loader stack.
     *
     * @return void
     */
    protected function prependToLoaderStack()
    {
        //可以看出，别名服务的启动关键就是这个spl_autoload_register，
        //这个函数我们应该很熟悉了，在自动加载中这个函数用于解析命名空间，在这里用于解析别名的真正类名。
        spl_autoload_register([$this, 'load'], true, true);
    }

    /**
     * Get the registered aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set the registered aliases.
     *
     * @param  array  $aliases
     * @return void
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Indicates if the loader has been registered.
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * Set the "registered" state of the loader.
     *
     * @param  bool  $value
     * @return void
     */
    public function setRegistered($value)
    {
        $this->registered = $value;
    }

    /**
     * Set the real-time facade namespace.
     *
     * @param  string  $namespace
     * @return void
     */
    public static function setFacadeNamespace($namespace)
    {
        static::$facadeNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Set the value of the singleton alias loader.
     *
     * @param  \Illuminate\Foundation\AliasLoader  $loader
     * @return void
     */
    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }

    /**
     * Clone method.
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
