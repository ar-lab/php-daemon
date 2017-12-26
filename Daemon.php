<?php

class Daemon
{

    private $_pidFile;

    /**
     * @var Job
     */
    private $_job = null;

    public function setPidFile($file = '/tmp/daemon.pid')
    {
        $this->_pidFile = $file;
    }

    public function setJob(Job $job)
    {
        $this->_job = $job;
    }

    public function stop()
    {
        $this->_job->complete();
    }

    public function start()
    {
        $childPid = pcntl_fork();

        if ($childPid) {
            exit();
        }

        posix_setsid();

        if ($this->isDaemonActive($this->_pidFile)) {
            die('Daemon already active');
        }

        $logDir = dirname(__FILE__) . '/../log';

        ini_set('error_log', $logDir . '/error.log');

        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        $STDIN = fopen('/dev/null', 'r');
        $STDOUT = fopen($logDir . '/app.log', 'ab');
        $STDERR = fopen($logDir . '/daemon.log', 'ab');

        $pcntlres = pcntl_signal(SIGTERM, array($this, 'signalHandler'));
        if ($pcntlres !== true) {
            die("invalid set signal handler");
        }

        file_put_contents($this->_pidFile, getmypid());

        $this->_job->run();
    }

    protected function isDaemonActive($pidFile)
    {
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, 0)) {
                return true;
            } else {
                if (!unlink($pidFile)) {
                    exit(-1);
                }
            }
        }
        return false;
    }

    protected function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM: {
                $this->stop();
                break;
            }
            default: {
            }
        }
    }

}