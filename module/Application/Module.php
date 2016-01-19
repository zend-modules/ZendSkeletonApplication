<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;


class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager         = $e->getApplication()->getEventManager();
        $serviceManager       = $e->getApplication()->getServiceManager();

        $moduleRouteListener  = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

		$serviceManager->get('LocaleManager')->setLocale('es_ES');
        if ($serviceManager->has('Translator') && ($e->getRouter() instanceof TranslatorAwareInterface)) {
            $translator = $serviceManager->get('Translator');

            if ($translator instanceof TranslatorInterface) {
                $e->getRouter()->setTranslator( $translator );
                $e->getRouter()->setTranslatorTextDomain('Zend\Mvc\Router');
            }
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
