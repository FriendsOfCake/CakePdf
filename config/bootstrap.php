<?php
use Cake\Event\EventManager;

EventManager::instance()
	->attach(
		function(Cake\Event\Event $event) {
			$controller = $event->subject();
			if ($controller->components()->has('RequestHandler')) {
				$controller->RequestHandler->viewClassMap('pdf', 'CakePdf.Pdf');
			}
		},
		'Controller.initialize'
	);
