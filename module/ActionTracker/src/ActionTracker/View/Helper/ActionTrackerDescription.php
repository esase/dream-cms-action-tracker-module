<?php
namespace ActionTracker\View\Helper;

use Application\Service\ApplicationServiceLocator as ApplicationServiceLocatorService;
use Zend\View\Helper\AbstractHelper;

class ActionTrackerDescription extends AbstractHelper
{
    /**
     * Service locator
     * @var object 
     */
    protected $serviceLocator;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->serviceLocator = ApplicationServiceLocatorService::getServiceLocator();
    }

    /**
     * Action description
     *
     * @param ArrayObject $action
     * @return string
     */
    public function __invoke($action)
    {
        return vsprintf($this->serviceLocator->
                get('Translator')->translate($action->description), unserialize($action->description_params));
    }
}