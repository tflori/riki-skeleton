<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace Test;

use App\Application;
use App\Config;
use App\Environment;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Whoops;

abstract class TestCase extends MockeryTestCase
{
    /** @var Application|m\Mock */
    protected $app;

    /** @var m\Mock[] */
    protected $mocks = [];

    /** @var string */
    protected $basePath;

    protected function setUp()
    {
        parent::setUp();
        $this->initApplication(realpath(__DIR__ . '/..'));
    }

    protected function tearDown()
    {
        parent::tearDown();
        Application::app()->destroy();
    }


    public function initApplication($basePath)
    {
        $this->basePath = $basePath;

        /** @var Application|m\Mock $app */
        $app = $this->app = m::mock(Application::class . '[]', [$basePath]);

        $this->initDependencies();
    }

    protected function initDependencies()
    {
        // basic dependencies the app needs at any time
        $this->mocks['environment'] = m::mock(Environment::class, [$this->basePath])->makePartial();
        $this->app->instance('environment', $this->mocks['environment']);
        $this->app->alias('environment', Environment::class);

        $this->mocks['config'] = m::mock(Config::class)->makePartial();
        $this->app->instance('config', $this->mocks['config']);
        $this->app->alias('config', Config::class);

        /** @var Whoops\Run|m\Mock $whoops */
        $whoops = $this->mocks['whoops'] = m::mock($this->app->get('whoops'));
        $this->app->instance('whoops', $whoops);
        $whoops->unregister();
        $whoops->shouldReceive('register')->andReturnSelf()->byDefault();
    }

    /**
     * Overwrite a protected or private $property from $object to $value
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    protected static function setProtectedProperty($object, string $property, $value)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $property = (new \ReflectionClass($object))->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
    }
}
