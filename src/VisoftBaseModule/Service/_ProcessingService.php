<?php

namespace VisoftBaseModule\Service;

use Doctrine\ORM\EntityManager;

/*
gearadmin --workers
*/

class ProcessingService
{
	private $worker = null;
	protected $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function createBackgroundProcess($functionName, $workload) 
	{
		// client
		$client = new \GearmanClient();
		$client->addServer('127.0.0.1', 4730);
		$result = $client->doBackground($functionName, $workload);
		$this->isWorkerExist($functionName);

		// worker
		$this->worker = new \GearmanWorker();
		$this->worker->addServer('127.0.0.1', 4730);
		$this->worker->setTimeout(240000);

		return $this;
	}

	private function isWorkerExist($functionName)
	{
		$gearmanStatus = $this->getGearmanStatus();
        foreach ($gearmanStatus['connections'] as $key => $connection) {
            if($connection['function'] === $functionName) {
                die("Worker found");
            }
        }
	}

	public function getWorker() 
	{
		return $this->worker;
	}

	public function run()
	{
        while(true) {
            echo "Witing a job... \n";
            $this->worker->work();
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $this->worker->returnCode() . "\n";
                break;
            }
        }
	}

    private function getGearmanStatus(){
        $status = null;
        // $handle = fsockopen($this->host,$this->port,$errorNumber,$errorString,30);
        $handle = fsockopen('127.0.0.1', 4730, $errorNumber, $errorString, 30);
        if($handle!=null){
            fwrite($handle,"status\n");
            while (!feof($handle)) {
                $line = fgets($handle, 4096);
                if( $line == ".\n") {
                    break;
                }
                if( preg_match("~^(.*)[ \t](\d+)[ \t](\d+)[ \t](\d+)~", $line, $matches) ) {
                    $function = $matches[1];
                    $status['operations'][$function] = array(
                        'function' => $function,
                        'total' => $matches[2],
                        'running' => $matches[3],
                        'connectedWorkers' => $matches[4],
                    );
                }
            }
            fwrite($handle,"workers\n");
            while (!feof($handle)) {
                $line = fgets($handle, 4096);
                if( $line==".\n"){
                    break;
                }
                // FD IP-ADDRESS CLIENT-ID : FUNCTION
                if( preg_match("~^(\d+)[ \t](.*?)[ \t](.*?) : ?(.*)~",$line,$matches) ){
                    $fd = $matches[1];
                    $status['connections'][$fd] = array(
                        'fd' => $fd,
                        'ip' => $matches[2],
                        'id' => $matches[3],
                        'function' => $matches[4],
                    );
                }
            }
            fclose($handle);
        }
        return $status;
    }
}