<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\GetOpt;
use GetOpt\Option;
use Whoops\Handler\PlainTextHandler;

class Kernel extends \Riki\Kernel
{
    /** @var GetOpt */
    protected $getOpt;

    /** @var string[] */
    protected $commands = [
        Command\Config\Cache::class,
    ];

    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'initWhoops'],
            [$this, 'createGetOpt'],
            [$this, 'loadCommands']
        );
    }

    public function handle(Arguments $arguments = null): int
    {
        $getOpt = $this->getOpt;

        // process arguments and catch user errors
        try {
            try {
                $getOpt->process();
            } catch (Missing $exception) {
                // catch missing exceptions if help is requested
                if (!$getOpt->getOption('help')) {
                    throw $exception;
                }
            }
        } catch (ArgumentException $exception) {
            file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $getOpt->getHelpText();
            exit;
        }

        $command = $getOpt->getCommand();
        if (!$command || $getOpt->getOption('help')) {
            echo $getOpt->getHelpText();
            return 0;
        }

        return call_user_func($command->getHandler(), $getOpt);
    }

    public function initWhoops(Application $app): bool
    {
        $app->whoops->pushHandler(new PlainTextHandler());

        return true;
    }

    public function createGetOpt(Application $app): bool
    {
        $this->getOpt = new GetOpt([
            Option::create('h', 'help')->setDescription('Show this help message')
        ]);

        return true;
    }

    public function loadCommands(Application $app): bool
    {
        foreach ($this->commands as $class) {
            $this->getOpt->addCommand(new $class);
        }

        return true;
    }
}
