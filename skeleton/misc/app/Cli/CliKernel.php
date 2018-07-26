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

class CliKernel extends \App\Kernel
{
    /** @var string[] */
    protected $commands = [
        Command\Config\Cache::class,
    ];

    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'registerDependencies']
        );
    }

    /**
     * @param \Riki\Application      $app
     * @param array|string|Arguments $arguments
     * @return int
     */
    public function handle(\Riki\Application $app, $arguments = null): int
    {
        $getOpt = $this->loadCommands($app);
        /** @var Console $console */
        $console = $app->console;

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

    public function getErrorHandlers(Application $app): array
    {
        return [new PlainTextHandler()];
    }

    public function registerDependencies(Application $app): bool
    {
        if (!$app->has(GetOpt::class)) {
            $app->add(GetOpt::class, GetOpt::class);
        }
        return true;
    }

    public function loadCommands(Application $app): GetOpt
    {
        /** @var GetOpt $getOpt */
        $getOpt = $app->get(GetOpt::class);

        $getOpt->addOptions([
            Option::create('h', 'help')
                ->setDescription('Show this help message'),
            Option::create('v', 'verbose')
                ->setDescription('Be verbose (can be stacked: -vv very verbose -vvv debug)'),
            Option::create('q', 'quiet')
                ->setDescription('Disable questions and show only warnings'),
        ]);

        foreach ($this->commands as $class) {
            $getOpt->addCommand(new $class($app, $app->console));
        }

        return $getOpt;
    }
}
