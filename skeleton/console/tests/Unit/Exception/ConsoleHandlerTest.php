<?php

namespace Test\Unit\Exception;

use App\Exception\ConsoleHandler;
use Hugga\Console;
use RuntimeException;
use Test\TestCase;
use Mockery as m;
use Whoops\Exception\Inspector;

class ConsoleHandlerTest extends TestCase
{
    protected function prepareHandler(\Throwable $exception = null): ConsoleHandler
    {
        $exception = $exception ?? new RuntimeException('Test Exception');

        $handler = new ConsoleHandler($this->app);
        $handler->setException($exception);
        $handler->setInspector(new Inspector($exception));

        return $handler;
    }

    /** @test */
    public function writesToConsole()
    {
        $handler = $this->prepareHandler();

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once();

        $handler->handle();
    }

    /** @test */
    public function messageContainsTheExceptionMessage()
    {
        $exceptionMessage = 'Test exception ' . md5(microtime());
        $handler = $this->prepareHandler(new RuntimeException($exceptionMessage));
        
        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once()->andReturnUsing(function (string $message) use ($exceptionMessage) {
                self::assertContains($exceptionMessage, $message);
            });

        $handler->handle();
    }

    /** @test */
    public function messageContainsTheErrorCode()
    {
        $code = mt_rand(1, 1000);
        $handler = $this->prepareHandler(new RuntimeException('Test Exception', $code));

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once()->andReturnUsing(function (string $message) use ($code) {
                self::assertContains((string)$code, $message);
            });

        $handler->handle();
    }

    /** @test */
    public function messageContainsFileAndLine()
    {
        $exception = new RuntimeException('Test Exception');
        $handler = $this->prepareHandler($exception);

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once()->andReturnUsing(function (string $message) use ($exception) {
                self::assertContains($exception->getFile(), $message);
                self::assertContains((string)$exception->getLine(), $message);
            });

        $handler->handle();
    }

    /** @test */
    public function replacesBasePathWithProjectPath()
    {
        $exception = new RuntimeException('Test Exception');
        $handler = $this->prepareHandler($exception);


        $this->mocks['config']->shouldReceive('env')->with('PROJECT_PATH')
            ->atLeast()->once()->andReturn('/project');

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once()->andReturnUsing(function (string $message) use ($exception) {
                $expected = '/project' . substr($exception->getFile(), strlen($this->app->getBasePath()));
                self::assertContains($expected, $message);
            });

        $handler->handle();
    }

    /** @test */
    public function messageContainsPreviousExceptions()
    {
        $innerException = new RuntimeException('Inner Exception', 23);
        $outerException = new RuntimeException('Outer Exception', 42, $innerException);
        $handler = $this->prepareHandler($outerException);

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'))
            ->once()->andReturnUsing(function (string $message) use ($innerException) {
                $messages = explode('Caused by', $message);
                self::assertCount(2, $messages);
                self::assertContains('Inner Exception', $messages[1]);
                self::assertContains('23', $messages[1]);
                self::assertContains((string)$innerException->getLine(), $messages[1]);
            });

        $handler->handle();
    }

    /** @test */
    public function writesTraceWithNormalWeight()
    {
        $handler = $this->prepareHandler();

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'), Console::WEIGHT_NORMAL)
            ->once();

        $handler->handle();
    }

    /** @test */
    public function traceContainsArgs()
    {
        $getException = function (
            array $array,
            string $string,
            string $class,
            int $int,
            float $double,
            bool $bool,
            $object
        ) {
            return new RuntimeException('Test exception');
        };
        $handler = $this->prepareHandler($getException(
            ['anything'],
            'a too long string will be cut off at 20 chars',
            ConsoleHandler::class,
            23,
            0.42,
            false,
            $this
        ));

        $this->mocks['console']->shouldReceive('writeError')->with(m::type('string'), Console::WEIGHT_NORMAL)
            ->once()->andReturnUsing(function (string $message) {
                self::assertContains('{closure}', $message);
                self::assertSame(1, preg_match(
                    '~\{closure\}\((.*)\)~',
                    $this->mocks['console']->format($message), // strip formatting
                    $match
                ));
                $args = $match[1];
                self::assertContains('array', $args);
                self::assertContains('"a too long string wi…"', $args);
                self::assertContains('"App\Exception\ConsoleHandler"', $args);
                self::assertContains('23', $args);
                self::assertContains('0.42', $args);
                self::assertContains('false', $args);
                self::assertContains(static::class, $args);
            });

        $handler->handle();
    }
}
