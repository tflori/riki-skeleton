<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use App\Exception\ConsoleHandler;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\Arguments;
use GetOpt\GetOpt;
use GetOpt\Option;
use Hugga\Console;

class CliKernel extends \App\Kernel
{
    /** @var Application */
    protected $app;

    /** @var string[] */
    protected static $commands = [
        Command\Config\Cache::class,
    ];

    /** @var GetOpt */
    protected $getOpt;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        // bootstrap the kernel
    }

    /**
     * @param array|string|Arguments $arguments
     * @return int
     */
    public function handle($arguments = null): int
    {
        $app = Application::app();
        $getOpt = $this->getGetOpt();
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
            $console->write($getOpt->getHelpText());
            return 128;
        }

        $command = $getOpt->getCommand();
        if (!$command || $getOpt->getOption('help')) {
            if ($cmdName = $getOpt->getOperand(0)) {
                $console->error(sprintf('Command %s not found', $cmdName));
            } elseif (!$getOpt->getOption('help')) {
                $console->error('No command given');
            }
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

    public function getErrorHandlers(): array
    {
        return [new ConsoleHandler($this->app)];
    }

    /**
     * Create a getOpt instance for this kernel, add default options and load registered commands.
     *
     * @return GetOpt
     */
    public function getGetOpt(): GetOpt
    {
        if (!$this->getOpt) {
            /** @var GetOpt $getOpt */
            $this->getOpt = $getOpt = $this->app->make(GetOpt::class);

            $getOpt->addOptions([
                Option::create('h', 'help')
                    ->setDescription('Show this help message'),
                Option::create('v', 'verbose')
                    ->setDescription('Be verbose (can be stacked: -vv very verbose -vvv debug)'),
                Option::create('q', 'quiet')
                    ->setDescription('Disable questions and show only warnings'),
            ]);

            foreach (static::$commands as $class) {
                if (!class_exists($class)) {
                    // @codeCoverageIgnoreStart
                    continue; // avoid errors for deleted commands
                    // @codeCoverageIgnoreEnd
                }
                $getOpt->addCommand($this->app->make($class, $this->app, $this->app->console));
            }
        }

        return $this->getOpt;
    }
}
