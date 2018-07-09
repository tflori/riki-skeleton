<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\Arguments;
use GetOpt\GetOpt;
use GetOpt\Option;
use Hugga\Console;
use Whoops\Handler\PlainTextHandler;

class Kernel extends \Riki\Kernel
{
    /** @var Application */
    protected $app;

    /** @var string[] */
    protected $commands = [
        Command\Config\Cache::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->addBootstrappers(
            [$this, 'initWhoops'],
            [$this, 'registerDependencies'],
            [$this, 'loadCommands']
        );
    }

    /**
     * @param array|string|Arguments $arguments
     * @return int
     */
    public function handle($arguments = null): int
    {
        /** @var GetOpt $getOpt */
        $getOpt = $this->app->get(GetOpt::class);
        /** @var Console $console */
        $console = $this->app->console;

        // process arguments and catch user errors
        try {
            try {
                $getOpt->process($arguments);
            } catch (Missing $exception) {
                // catch missing exceptions if help is requested
                if (!$getOpt->getOption('help')) {
                    throw $exception;
                }
            }
        } catch (ArgumentException $exception) {
            $console->error($exception->getMessage());
            $console->write(PHP_EOL . $getOpt->getHelpText());
            exit;
        }

        $command = $getOpt->getCommand();
        if (!$command || $getOpt->getOption('help')) {
            $console->write($getOpt->getHelpText());
            return 0;
        }

        if ($verbose = $getOpt->getOption('verbose')) {
            while ($verbose--) {
                $console->increaseVerbosity();
            }
        } elseif ($getOpt->getOption('quiet')) {
            $console->setVerbosity(Console::WEIGHT_HIGH);
        }

        return call_user_func($command->getHandler(), $getOpt);
    }

    public function initWhoops(Application $app): bool
    {
        $app->appendWhoopsHandler(new PlainTextHandler());
        return true;
    }

    public function registerDependencies(Application $app): bool
    {
        if (!$app->has(GetOpt::class)) {
            $app->share(GetOpt::class, GetOpt::class);
        }
        return true;
    }

    public function loadCommands(Application $app): bool
    {
        /** @var GetOpt $getOpt */
        $getOpt = $app->get(GetOpt::class);

        if (!$getOpt->hasOptions()) {
            $getOpt->addOptions([
                Option::create('h', 'help')->setDescription('Show this help message'),
                Option::create('v', 'verbose')->setDescription('Be verbose (can be stacked: -vv very verbsoe -vvv debug)'),
                Option::create('q', 'quiet')->setDescription('Disable questions and show only warnings'),
            ]);
        }

        if (!$getOpt->hasCommands()) {
            foreach ($this->commands as $class) {
                $getOpt->addCommand(new $class($app, $app->console));
            }
        }

        return true;
    }
}
