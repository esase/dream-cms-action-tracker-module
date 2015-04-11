<?php
namespace ActionTracker\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class ActionTrackerBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

ActionTrackerBootstrap::init();