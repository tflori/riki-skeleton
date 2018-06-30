<?php

namespace Skeleton;

use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;

class Skeleton
{
    /** @var array */
    protected $colors = [];

    /** @var bool */
    protected $pretend = false;

    /** @var bool */
    protected $quiet = false;

    /** @var string */
    protected $debug = null;

    public function __construct()
    {
        error_reporting(E_ALL^E_WARNING^E_NOTICE);

        $this->colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'cyan' => "\033[36m",
            'reset' => "\033[0m",
        ];
    }

    public function run()
    {
        $getOpt = new GetOpt([
            Option::create('h', 'help')
                ->setDescription('Show this help screen'),
            Option::create('q', 'quiet')
                ->setDescription('Suppress output and questions'),
            Option::create('p', 'project-name', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('Define the project name'),
            Option::create('n', 'source-namespace', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('Define the namespace for sources')
                ->setValidation(...$this->createValidator('namespace')),
            Option::create('D', 'no-docker')
                ->setDescription('Don\'t create docker files'),
            Option::create(null, 'debug', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('Print the result for files matching <arg>'),
            Option::create(null, 'pretend')
                ->setDescription('Do not execute anything'),
        ], [
            GetOpt::SETTING_STRICT_OPERANDS => true
        ]);
        $getOpt->addCommand(
            Command::create('setup', [$this, 'setup'])
        );
        $getOpt->addCommand(
            Command::create('create-project', [$this, 'createProject'])
                ->addOperand(Operand::create('target', Operand::REQUIRED))
        );

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

        // show help and quit
        $command = $getOpt->getCommand();
        if (!$command || $getOpt->getOption('help')) {
            echo $getOpt->getHelpText();
            exit;
        }

        $this->pretend = (bool)$getOpt->getOption('pretend');
        $this->quiet = (bool)$getOpt->getOption('quiet');
        $this->debug = (bool)$getOpt->getOption('debug');

        $vars = [
            'projectName' => $getOpt->getOption('project-name'),
            'sourceNamespace' => $getOpt->getOption('source-namespace'),
        ];

        if ($getOpt->getOption('no-docker')) {
            $vars['useDocker'] = false;
        }

        // call the requested command
        call_user_func($command->getHandler(), $vars, ...$getOpt->getOperands());
    }

    /**
     * @param array $vars
     * @throws \Exception
     */
    protected function setup(array $vars)
    {
        $target = getcwd();
        $this->deploy($vars, $target);

        // cleanup
        $this->remove($target . '/setup');
        $this->remove($target . '/skeleton');
        $this->remove($target . '/composer.lock');
        $this->remove($target . '/vendor');
        $this->remove($target . '/docker-compose.dev.example.yml');
        $this->remove($target . '/docker.dev');
        $this->remove($target . '/composer.dev.json');
        if (file_exists($target . '/.git')) {
            $this->remove($target . '/.git');
        }
        $this->cleanupGitignore($target . '/.gitignore');
    }

    /**
     * @param array  $vars
     * @param string $target
     * @throws \Exception
     */
    protected function createProject(array $vars, string $target)
    {
        if (!file_exists($target)) {
            $this->makeDir($target);
        } elseif (count(scandir($target)) !== 2) {
            $this->fail('Target directory is not empty');
        }
        $this->deploy($vars, $target);
    }

    /**
     * @param array  $vars
     * @param string $target
     * @throws \Exception
     */
    protected function deploy(array $vars, string $target)
    {
        $vars['projectRoot'] = realpath($target) ?: $target;
        $vars['baseName'] = $baseName = basename($target);
        $vars['binaryFile'] = $binaryFile = $baseName;
        $vars['basePath'] = $baseName;

        if (empty($vars['projectName'])) {
            $vars['projectName'] = $this->ask(
                'What\'s the name of your project?',
                get_current_user() . '/' . $baseName
            );
        }

        if (empty($vars['sourceNamespace'])) {
            $vars['sourceNamespace'] = $this->ask(
                'What\'s the namespace for your sources?',
                studlyCase($baseName),
                $this->createValidator('namespace', 'required')
            );
        }

        if (!isset($vars['useDocker'])) {
            $answer = $this->ask('Do you want to use docker?', 'y', ['y', 'n']);
            $vars['useDocker'] = $useDocker = $answer === 'y';
        }


        $this->deployFiles(__DIR__ . '/misc', $target, $vars);
        if ($useDocker) {
            $this->deployFiles(__DIR__ . '/docker', $target, $vars);
        }

        $this->rename($target . '/bin/cli', $target . '/bin/' . $binaryFile);
        $this->chmod($target . '/bin/' . $binaryFile, umask() ^ 0777 | 0111);
    }

    /**
     * Deploy files from $templatePath to $rootPath
     *
     * @param string $templatePath
     * @param string $rootPath
     * @param array  $vars
     * @throws \Exception
     */
    protected function deployFiles(string $templatePath, string $rootPath, $vars = [])
    {
        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templatePath)) as $fileInfo) {
            // skip directories
            if ($fileInfo->isDir()) {
                continue;
            }

            // determine target path
            $target = str_replace('.tpl', '', $rootPath . substr($fileInfo->getPathname(), strlen($templatePath)));

            // create the parent directory
            if (!file_exists(dirname($target))) {
                $this->makeDir(dirname($target));
            }

            if (strpos($fileInfo->getPathname(), '.tpl') !== false) {
                $content = $this->parse($fileInfo->getPathname(), $vars);

                if ($this->debug && strpos($target, $this->debug) !== false) {
                    $this->info('With the following content:');
                    echo rtrim($content) . PHP_EOL . PHP_EOL;
                    continue;
                }

                $this->write($target, $content);
            } else {
                $this->copy($fileInfo->getPathname(), $target);
            }
        }
    }

    /**
     * Parse $template as php using $vars
     *
     * @param string $template
     * @param array $vars
     * @return string
     */
    protected function parse(string $template, array $vars)
    {
        ob_start();
        extract($vars, EXTR_SKIP);
        include($template);
        return ob_get_clean();
    }

    /**
     * Ask the user a question
     *
     * @param string         $question
     * @param mixed          $default
     * @param array|callable $answers Pre defined answers or validator
     * @return mixed
     */
    protected function ask(string $question, $default = null, $answers = [])
    {
        if ($this->quiet) {
            return $default;
        }

        $validate = null;
        if (is_callable($answers[0])) {
            $validate = $answers;
            $answers = [];
        }
        if (!empty($answers) && !in_array($default, $answers)) {
            return $default;
        }

        echo $question . ' ';

        if (!empty($answers)) {
            echo '[ ' . implode(' / ', array_map(function ($answer) use ($default) {
                return $answer == $default ? strtoupper($answer) : $answer;
            }, $answers)) . ' ] ';
        } elseif (!empty($default)) {
            echo '[ ' . $default . ' ] ';
        }

        $answer = trim(fgets(STDIN));
        if ($answer === '') {
            $answer = $default;
        }

        if (!empty($answers) && !in_array(strtolower($answer), $answers)) {
            $this->warn($answer . ' is not valid' . PHP_EOL);
            return ask($question, $default, $answers);
        } elseif ($validate && call_user_func($validate[0], $answer) !== true) {
            $this->warn(call_user_func($validate[1], null, $answer) . PHP_EOL);
            return ask($question, $default, $validate);
        }
        echo PHP_EOL;
        return !empty($answers) ? strtolower($answer) : $answer;
    }

    /**
     * Create a function that validates $types
     *
     * @param string ...$types
     * @return \Closure[]
     */
    protected function createValidator(...$types)
    {
        $message = false;
        return [function ($value) use ($types, &$message) {
            foreach ($types as $type) {
                switch ($type) {
                    case 'namespace':
                        if (!preg_match('/[A-Z][a-zA-Z0-9]*(\\[A-Z][a-zA-Z0-9]*)*/', $value)) {
                            $message = 'has to start with uppercase letter and contain only letters and numbers';
                            return false;
                        }
                        if (in_array($value, ['App', 'Test'])) {
                            $message = 'namespace already in use';
                            return false;
                        }
                        break;
                    case 'required':
                        if (empty($value)) {
                            $message = 'cannot be empty';
                            return false;
                        }
                        break;
                }
            }

            return true;
        }, function (\GetOpt\Describable $obj = null, $value) use (&$message) {
            return $value . ' is invalid ' . ($obj ? 'for ' . $obj->describe() . ' ' : '') . '(' . $message . ')';
        }];
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    protected function makeDir(string $path)
    {
        if ($this->pretend) {
            static $created = [];
            if (!in_array($path, $created)) {
                $this->info('mkdir ' . $path);
                $created[] = $path;
            }
            return;
        }

        if (!mkdir($path, umask() ^ 0777, true)) {
            throw new \Exception('Could not create directory ' . $path);
        }
    }

    /**
     * @param string $path
     * @param string $content
     * @throws \Exception
     */
    protected function write(string $path, string $content)
    {
        if ($this->pretend) {
            $this->info('write ' . $path);
            return;
        }

        if (file_put_contents($path, $content) === false) {
            throw new \Exception('Could not write file ' . $path);
        }
    }

    /**
     * @param string $oldName
     * @param string $newName
     * @throws \Exception
     */
    protected function rename(string $oldName, string $newName)
    {
        if ($this->pretend) {
            $this->info('rename ' . $oldName . ' to ' . $newName);
            return;
        }

        if (!rename($oldName, $newName)) {
            throw new \Exception('Could not rename file ' . $oldName . ' to ' . $newName);
        }
    }

    /**
     * @param string $source
     * @param string $dest
     * @throws \Exception
     */
    protected function copy(string $source, string $dest)
    {
        if ($this->pretend) {
            $this->info('copy ' . $source . ' to ' . $dest);
            return;
        }

        if (!copy($source, $dest)) {
            throw new \Exception('Could not copy file ' . $source . ' to ' . $dest);
        }
    }

    /**
     * @param string $path
     * @param int    $mode
     * @throws \Exception
     */
    protected function chmod(string $path, int $mode)
    {
        if ($this->pretend) {
            $this->info(sprintf('change mode for %s to %s', $path, decoct($mode)));
            return;
        }
        if (!chmod($path, $mode)) {
            throw new \Exception(sprintf('Could not change file mode for %s to %s', $path, decoct($mode)));
        }
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    protected function remove(string $path)
    {
        if ($this->pretend) {
            $this->info('remove ' . $path);
            return;
        }

        if (!$this->removeRecursive($path)) {
            throw new \Exception('Could not remove ' . $path);
        }
    }

    protected function info($message)
    {
        if ($this->quiet) {
            return;
        }
        echo $this->colors['green'] . $message . $this->colors['reset'] . PHP_EOL;
    }

    protected function warn($message)
    {
        if ($this->quiet) {
            return;
        }
        echo $this->colors['yellow'] . $message . $this->colors['reset'] . PHP_EOL;
    }

    protected function fail($reason)
    {
        fwrite(STDERR, $this->colors['red'] . $reason . $this->colors['reset'] . PHP_EOL);
        exit(1);
    }

    protected function removeRecursive(string $path)
    {
        if (!is_dir($path)) {
            return unlink($path);
        }

        $dh = opendir($path);
        while ($file = readdir($dh)) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $filePath = $path . '/' . $file;
            $this->removeRecursive($filePath);
        }
        return rmdir($path);
    }

    protected function cleanupGitignore(string $path)
    {
        $file = file($path);
        if ($this->pretend) {
            $this->info(sprintf(
                'removing all lines from gitignore beginning in line %d',
                array_search("### remove all this\n", $file)
            ));
            return;
        }
        file_put_contents($path, implode('', array_slice($file, 0, array_search("### remove all this\n", $file))));
    }
}

/**
 * Convert snake_case, kebab-case and StudlyCase to camelCase
 *
 * @param string $str
 * @return string
 */
function camelCase(string $str): string
{
    return preg_replace_callback('/[^a-z]([a-z])?/i', function ($match) {
        return isset($match[1]) ? strtoupper($match[1]) : '';
    }, lcfirst($str));
}

/**
 * Convert snake_case, kebab-case and camelCase to StudlyCase
 *
 * @param string $str
 * @return string
 */
function studlyCase(string $str): string
{
    return ucfirst(camelCase($str));
}
