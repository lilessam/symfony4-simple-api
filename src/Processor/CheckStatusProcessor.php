<?php

namespace App\Processor;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CheckStatusProcessor
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __invoke(MyMessage $message)
    {
        $process = new Process('php ../bin/console app:check-status ' . $this->id);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}