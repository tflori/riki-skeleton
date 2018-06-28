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

    /** @var Console */
    protected $console;

    /** @var string[] */
    protected $commands = [
        Command\Config\Cache::class,
    ];

    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'initWhoops'],
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

        if ($verbose = $getOpt->getOption('verbose')) {
            while ($verbose--) {
                $this->console->increaseVerbosity();
            }
        } elseif ($getOpt->getOption('quiet')) {
            $this->console->setVerbosity(Console::WEIGHT_HIGH);
        }

        return call_user_func($command->getHandler(), $getOpt);
    }

    public function initWhoops(Application $app): bool
    {
        $app->appendWhoopsHandler(new PlainTextHandler());
        return true;
    }

    public function loadCommands(Application $app): bool
    {
        $this->console = $app->get('console');
        $this->getOpt = new GetOpt([
            Option::create('h', 'help')->setDescription('Show this help message'),
            Option::create('v', 'verbose')->setDescription('Be verbose (can be stacked: -vv very verbsoe -vvv debug)'),
            Option::create('q', 'quiet')->setDescription('Disable questions and show only warnings'),
        ]);

        foreach ($this->commands as $class) {
            $this->getOpt->addCommand(new $class($app, $this->console));
        }

        return true;
    }
}
