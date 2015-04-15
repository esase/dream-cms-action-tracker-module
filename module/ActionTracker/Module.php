<?php
namespace ActionTracker;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use ActionTracker\Event\ActionTrackerEvent;

class Module
{
    /**
     * Service locator
     * @var object
     */
    public $serviceLocator;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initEvents']);
    }

    /**
     * Init events
     * 
     * @param object $e
     */
    public function initEvents(ModuleEvent $e)
    {
        $model = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('ActionTracker\Model\ActionTrackerBase');

        $actions = $model->getActivatedActions();

        // bind all activated events
        if (count($actions)) {
            $eventManager = ActionTrackerEvent::getEventManager();

            foreach ($actions as $action) {
                $eventManager->attach($action->name, function ($e) use ($model, $action) {
                    $model->logAction($action->
                            action_id, $e->getParam('description'), $e->getParam('description_params'));
                });
            }
        }
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
            ]
        ];
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return [
            'invokables' => [
                'actionTrackerDescription' => 'ActionTracker\View\Helper\ActionTrackerDescription'
            ]
        ];
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}