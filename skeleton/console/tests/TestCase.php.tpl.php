<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->append('use App\\\\Environment;', 'use Hugga\Console;')
    ->append('        \$whoops->shouldReceive\(\'register\'\)->andReturnSelf\(\)->byDefault\(\);', '        
        /** @var Console|m\Mock $console */
        $console = $this->mocks[\'console\'] = m::mock(Console::class)->makePartial();
        $console->__construct();
        $console->disableAnsi();
        $console->setStdout(fopen(\'php://memory\', \'w\'));
        $console->setStderr(fopen(\'php://memory\', \'w\'));
        $this->mocks[\'console\']->shouldNotReceive([\'read\', \'readLine\', \'readUntil\']);
        $this->app->instance(\'console\', $this->mocks[\'console\']);');
