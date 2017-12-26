<?php

abstract class Job
{

    protected $_complete = false;

    public function complete()
    {
        $this->_complete = true;
    }

    public function run()
    {
        while (!$this->_complete) {
            $this->processJob();
            pcntl_signal_dispatch();
        }
    }

    abstract protected function processJob();

}