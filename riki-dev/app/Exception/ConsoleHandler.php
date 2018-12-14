<?php

namespace App\Exception;

use App\Application;
use App\Config;
use App\Environment;
use Hugga\Console;
use Whoops\Exception\Frame;
use Whoops\Handler\Handler;

class ConsoleHandler extends Handler
{
    /** @var Application */
    protected $app;

    /** @var Console */
    protected $console;

    /** @var Config */
    protected $config;

    /**
     * ConsoleHandler constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle()
    {
        $this->console = $this->app->console;
        $this->config = $this->app->config;

        $this->console->writeError($this->generateMessage($this->getException()) . PHP_EOL);

        $this->console->writeError(PHP_EOL . $this->generateTrace() . PHP_EOL, Console::WEIGHT_NORMAL);
        
        return self::QUIT;
    }

    protected function replacePath(string $path): string
    {
        $projectPath = $this->config->env('PROJECT_PATH');
        if ($projectPath) {
            $path = preg_replace(
                '~^' . $this->app->getBasePath() . '~',
                $projectPath,
                $path
            );
        }

        return $path;
    }

    protected function generateMessage(\Throwable $exception): string
    {
        $message = sprintf(
            '${b}%s${yellow}%s${r}: ${red;b}%s ${grey}in ${blue}%s ${grey}on line ${yellow}%d${r}',
            get_class($exception),
            $exception->getCode() ? '(' . $exception->getCode() . ')' : '',
            $exception->getMessage(),
            $this->replacePath($exception->getFile()),
            $exception->getLine()
        );

        if ($exception->getPrevious()) {
            $message .= "\n\nCaused by\n" . $this->generateMessage($exception->getPrevious());
        }

        return $message;
    }

    protected function generateTrace(): string
    {
        static $template = PHP_EOL . '${b}%3d.${r} ${light-magenta}%s->%s${r}(%s)' . PHP_EOL .
                           '     ${grey}in ${blue}%s ${grey}on line ${yellow}%d${r}';
        $inspector = $this->getInspector();
        $frames = $inspector->getFrames();

        $response = '${b}Stack trace${r}:';

        $line = 1;
        /** @var Frame $frame */
        foreach ($frames as $frame) {
            $response .= str_replace('{none}->', '', sprintf(
                $template,
                $line,
                $frame->getClass() ?: '{none}',
                $frame->getFunction(),
                $this->generateArgs($frame->getArgs()),
                $this->replacePath($frame->getFile()),
                $frame->getLine()
            ));

            $line++;
        }

        return $response;
    }

    protected function generateArgs(array $args): string
    {
        $result = [];
        foreach ($args as $arg) {
            switch (gettype($arg)) {
                case 'object':
                    $result[] = get_class($arg);
                    break;

                case 'string':
                    if (!class_exists($arg)) {
                        $arg = strlen($arg) > 20 ? substr($arg, 0, 20) . '…' : $arg;
                    }
                    $result[] = sprintf('"%s"', $arg);
                    break;

                case 'integer':
                case 'double':
                    $result[] = (string)$arg;
                    break;

                case 'boolean':
                    $result[] = $arg ? 'true' : 'false';
                    break;

                default:
                    $result[] = gettype($arg);
                    break;
            }
        }
        return implode(', ', $result);
    }
}
