<?php

namespace Test\Cli\Config;

use App\Config;
use Test\Cli\TestCase;

class CacheTest extends TestCase
{
    /** @test */
    public function stopsWhenCachingIsDisabled()
    {
        $this->mocks['environment']->shouldReceive('canCacheConfig')->with()
            ->once()->andReturn(false);

        $result = $this->start('config:cache');

        self::assertEquals('The environment does not allow to cache the configuration!', trim($result['output']));
        self::assertSame(0, $result['returnVar']);
    }

    /** @test */
    public function failsWhenDirectoryCanNotBeCreated()
    {
        $cachePath = '/var/cache/riki-test/config.spo';
        if (posix_getuid() === 0) {
            $this->markTestSkipped('This test can not be executed from super user');
            return;
        } elseif (file_exists(dirname($cachePath)) || is_writeable(dirname(dirname($cachePath)))) {
            $this->markTestSkipped('Directory ' . dirname($cachePath) . ' exists or could be created');
            return;
        }

        $this->mocks['environment']->shouldReceive('getConfigCachePath')->with()
            ->once()->andReturn($cachePath);

        $result = $this->start('config:cache');

        self::assertEquals('Could not create parent directory for caching!', trim($result['errors']));
        self::assertSame(1, $result['returnVar']);
    }

    /** @test */
    public function failsWhenDirectoryIsAFile()
    {
        $cachePath = '/etc/passwd/config.spo';
        $this->mocks['environment']->shouldReceive('getConfigCachePath')->with()
            ->once()->andReturn($cachePath);

        $result = $this->start('config:cache');

        self::assertEquals('Cache directory is not writeable!', trim($result['errors']));
        self::assertSame(2, $result['returnVar']);
    }

    /** @test */
    public function failsWhenFileIsNotWriteable()
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('This test can not be executed from super user');
            return;
        }
        $cachePath = $this->getFileFromRoot('/var');
        if (!$cachePath) {
            $this->markTestSkipped('Could not find a non-writeable file for test');
            return;
        }

        $this->mocks['environment']->shouldReceive('getConfigCachePath')->with()
            ->once()->andReturn($cachePath);

        $result = $this->start('config:cache');

        self::assertEquals('Failed to cache the configuration!', trim($result['errors']));
        self::assertSame(3, $result['returnVar']);
    }

    /** @test */
    public function cachesTheConfig()
    {
        $cachePath = '/tmp/riki-test-config.spo';
        $this->mocks['environment']->shouldReceive('getConfigCachePath')->with()
            ->once()->andReturn($cachePath);

        $result = $this->start('config:cache');

        self::assertFileExists($cachePath);
        self::assertSame(
            serialize(new Config($this->mocks['environment'])),
            file_get_contents($cachePath)
        );
        self::assertEquals('Configuration cache created successfully!', trim($result['output']));
        self::assertSame(0, $result['returnVar']);
    }

    /**
     * Get a file owned by root
     *
     * @param string $dir
     * @throws \Exception
     */
    protected function getFileFromRoot(string $dir)
    {
        $dh = opendir($dir);
        if (!$dh) {
            throw new \Exception('Could not open dir ' . $dir . ' for reading');
        }

        while ($file = readdir($dh)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if (is_readable($path) && $fileFromRoot = $this->getFileFromRoot($path)) {
                    return $fileFromRoot;
                }
                continue;
            } elseif (@fileowner($path) === 0 && !is_writeable($path) && is_writeable(dirname($path))) {
                return $path;
            }
        }

        return null;
    }
}
