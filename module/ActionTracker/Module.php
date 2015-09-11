<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace ActionTracker;

use ActionTracker\Event\ActionTrackerEvent;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;

class Module
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    public $serviceLocator;

    /**
     * Init
     *
     * @param \Zend\ModuleManager\ModuleManagerInterface $moduleManager
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
     * @param \Zend\ModuleManager\ModuleEvent $e
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
                    if ($model->getModuleInfo('ActionTracker')) {
                        $model->logAction($action->
                                action_id, $e->getParam('description'), $e->getParam('description_params'));
                    }
                });
            }
        }
    }

    /**
     * Return auto loader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                ]
            ]
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
     *
     * @return array
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
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}