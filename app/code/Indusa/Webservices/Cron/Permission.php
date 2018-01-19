<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Logger\Logger;
	
	class Permission {
		
		protected $logger;
		protected $_requestQueueFactory;
		
		public function __construct(
		\Indusa\Webservices\Logger\Logger $loggerInterface
		){
			$this->logger = $loggerInterface;
		}	
		public function execute() {
			shell_exec("chmod -R 777 var/");
		}
		
	}
?>