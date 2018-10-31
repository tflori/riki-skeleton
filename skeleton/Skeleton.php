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
    const AVAILABLE_FEATURES = [
        'docker' => 'y',
        'console' => 'n',
        'routing' => 'n',
    ];

    /** @var array */
    protected $colors = [];

    /** @var bool */
    protected $pretend = false;

    /** @var bool */
    protected $quiet = false;

    /** @var string */
    protected $debug = null;

    protected $excludes = [
        '~^/vendor/.*$~',
        '~^/bin/(?!cli|\.gitignore).*$~',
        '~^/composer.dev.json$~',
        '~^/composer.json$~',
        '~^/composer.lock$~',
    ];

    /** @var string[] */
    protected $contents = [];

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
            Option::create('a', 'all-features')
                ->setDescription('Install all features'),
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
        $getOpt->addCommand(
            Command::create('dev', [$this, 'createDev'])
                ->addOption(Option::create(null, 'start'))
        );

        foreach (array_keys(self::AVAILABLE_FEATURES) as $feature) {
            $getOpt->addOption(
                Option::create(null, $feature)
                    ->setDescription('Add feature ' . $feature . ' without asking')
            );
            $getOpt->addOption(
                Option::create(null, 'no-' . $feature)
                    ->setDescription('Skip asking for feature ' . $feature)
            );
        }

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
        $this->debug = $getOpt->getOption('debug');

        $vars = [
            'projectName' => $getOpt->getOption('project-name'),
            'sourceNamespace' => $getOpt->getOption('source-namespace'),
            'features' => [],
        ];

        foreach (array_keys(self::AVAILABLE_FEATURES) as $feature) {
            if ($getOpt->getOption($feature) || $getOpt->getOption('all-features')) {
                $vars['features'][$feature] = true;
            } elseif ($getOpt->getOption('no-' . $feature)) {
                $vars['features'][$feature] = false;
            }
        }

        // call the requested command
        call_user_func($command->getHandler(), $getOpt, $vars);
    }

    /**
     * @param GetOpt $getOpt
     * @param array $vars
     */
    protected function setup(GetOpt $getOpt, array $vars)
    {
        $target = getcwd();
        $this->deploy($vars, $target);

        // cleanup
        $this->remove($target . '/setup');
        $this->remove($target . '/riki-dev');
        $this->remove($target . '/skeleton');
        $this->remove($target . '/composer.lock');
        $this->remove($target . '/vendor');
        $this->remove($target . '/.git');
        $this->remove($target . '/.travis.yml');
    }

    /**
     * @param GetOpt $getOpt
     * @param array $vars
     * @param string $target
     */
    protected function createProject(GetOpt $getOpt, array $vars)
    {
        $target = $getOpt->getOperand('target');
        if (!file_exists($target)) {
            $this->makeDir($target);
        } elseif (count(scandir($target)) !== 2) {
            $this->fail('Target directory is not empty');
        }
        $this->deploy($vars, $target);
    }

    /**
     * Creates and starts the development environment
     *
     * @param GetOpt $getOpt
     */
    protected function createDev(GetOpt $getOpt)
    {
        $vars = [
            'baseName' => 'riki',
            'binaryFile' => 'riki',
            'basePath' => 'riki',
            'projectName' => 'nobody/riki',
            'sourceNamespace' => 'Riki',
        ];
        $target = realpath(__DIR__ . '/../riki-dev');

        $this->info('Building development environment in path ' . $target);

        $this->deployFiles(__DIR__ . '/misc', $target, $vars);
        foreach (array_keys(self::AVAILABLE_FEATURES) as $feature) {
            $this->deployFiles(__DIR__ . '/' . $feature, $target, $vars);
        }
        $this->deployFiles(__DIR__ . '/dev', $target, $vars);

        $this->rename($target . '/bin/cli', $target . '/bin/' . $vars['binaryFile']);
        $this->chmod($target . '/bin/' . $vars['binaryFile'], umask() ^ 0777 | 0111);

        if ($getOpt->getOption('start')) {
            // start the environment
            chdir($target);
            passthru('composer update');
            passthru('docker-compose build');
            passthru('docker-compose start');
        }
    }

    /**
     * @param array  $vars
     * @param string $target
     * @throws \Exception
     */
    protected function deploy(array $vars, string $target)
    {
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

        $this->deployFiles(__DIR__ . '/misc', $target, $vars);

        foreach (self::AVAILABLE_FEATURES as $feature => $default) {
            if (!file_exists(__DIR__ . '/' . $feature)) {
                continue;
            }

            if (!isset($vars['features'][$feature])) {
                $answer = $this->ask('Do you want to use ' . $feature . '?', $default, ['y', 'n']);
                $vars['features'][$feature] = $answer === 'y';
            }

            if (!$vars['features'][$feature]) {
                continue;
            }

            $this->deployFiles(__DIR__ . '/' . $feature, $target, $vars);
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

            $relativePath = substr($fileInfo->getPathname(), strlen($templatePath));
            if ($this->isExcluded($relativePath)) {
                continue;
            }
            $target = $rootPath . $relativePath;

            // create the parent directory
            if (!file_exists(dirname($target))) {
                $this->makeDir(dirname($target));
            }

            if (strpos($fileInfo->getPathname(), '.tpl') !== false) {
                list($content, $target) = $this->parse($fileInfo->getPathname(), $target, $vars);

                if ($this->debug && strpos($target, $this->debug) !== false) {
                    $this->info('With the following content:');
                    echo rtrim($content) . PHP_EOL . PHP_EOL;
                }

                $this->write($target, $content);
            } else {
                $this->write($target, file_get_contents($fileInfo->getPathname()));
            }
        }
    }

    /**
     * Parse $template for $target as php using $vars
     *
     * Returns content and new target path
     *
     * @param string $template
     * @param array $vars
     * @return string[]
     */
    protected function parse(string $template, string $target, array $vars)
    {
        extract($vars, EXTR_SKIP);

        if (strpos($template, '.tpl.php') === false) {
            // simple template
            ob_start();
            include($template);
            $content = ob_get_clean();
            $target = str_replace('.tpl', '', $target);
        } else {
            // use content class as helper
            $target = str_replace('.tpl.php', '', $target);
            $content = $this->contents[$target] ?? '';
            if (file_exists($target)) {
                $content = file_get_contents($target);
            }
            $content = new Content($content);
            include($template);
        }

        return [(string)$content, $target];
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
        if (isset($answers[0]) && is_callable($answers[0])) {
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
            $this->contents[$path] = $content;
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

        if (!file_exists($path)) {
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

    protected function isExcluded(string $path)
    {
        foreach ($this->excludes as $exclude) {
            if (preg_match($exclude, $path)) {
                return true;
            }
        }

        return false;
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
