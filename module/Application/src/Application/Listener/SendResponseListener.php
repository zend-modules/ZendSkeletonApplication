<?php
namespace Application\Listener;

use Locale;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\I18n\Translator as MvcTranslator;
use Zend\View\HelperPluginManager as ViewHelperPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class SendResponseListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'));
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, array($this, 'onFinish'));
    }

    protected function getLocale(ServiceLocatorInterface $serviceLocator)
    {
        $locale = null;

        if ($serviceLocator->has('Translator')) {
            $translator = $serviceLocator->get('Translator');
            if ($translator instanceof MvcTranslator) {
                $translator = $translator->getTranslator();
            }
        
            if (method_exists($translator, 'getLocale')) {
                $locale = $translator->getLocale();
                if (extension_loaded('intl')) {
                    $locale = Locale::canonicalize($locale);
                }
            }
        }

        if ($locale === null) {
            if (extension_loaded('intl')) {
                $locale = Locale::getDefault();
            }
        }

        return $locale;
    }

    protected function setViewHelpers(ServiceLocatorInterface $services)
    {
        // Wee need a ViewHelperManager
        if (!$services->has('ViewHelperManager')) {
            return;
        }

        $viewHelperManager = $services->get('ViewHelperManager');
        if (!$viewHelperManager instanceof ViewHelperPluginManager) {
            return;
        }

        // htmlTag view helper
        if ( $viewHelperManager->has('htmlTag') ) {
            $htmlTag = $viewHelperManager->get('htmlTag');

            // Schema
            $attributes = $htmlTag->getAttributes();

            if ( !isset($attributes['itemscope']) ) {
                $htmlTag->setAttribute('itemscope', '');
            }

            if ( !isset($attributes['itemtype']) ) {
                $htmlTag->setAttribute('itemtype', 'http://schema.org/WebPage');
            }

            // Language
            $locale   = $this->getLocale($services);
            if ($locale !== null) {
                if (extension_loaded('intl')) {
                    $htmlTag->setAttribute('lang', \Locale::getPrimaryLanguage($locale));
                } else {
                    $lang = preg_replace('/\-/', '_', $locale);
                    $lang = explode('_', $lang, 2);
                    $lang = $lang[0];
                    $htmlTag->setAttribute('lang', $lang);
                }
            }
        }
    }

    /**
     * Listen to the "finish" event and attempt to inject the HTTP headers.
     * 
     * @param MvcEvent $event
     */
    public function onFinish(MvcEvent $event)
    {
        $response = $event->getResponse();
        if (!$response instanceof HttpResponse) {
            return;
        }

        $services = $event->getApplication()->getServiceManager();
        $locale   = $this->getLocale($services);
        if ($locale !== null) {
            // Canonicalized locales use '_' we want '-'
            $isoLocale = preg_replace('/\_/', '-', $locale);
            // HTTP headers
            $response->getHeaders()->addHeaderLine('Content-Language', $isoLocale);
        }
    }

    /**
     * Listen to the "render" event and attempt to inject some extra data.
     * 
     * @param MvcEvent $event
     */
    public function onRender(MvcEvent $event)
    {
        $response = $event->getResponse();
        if (!$response instanceof HttpResponse) {
            return;
        }

        $services = $event->getApplication()->getServiceManager();

        $this->setViewHelpers($services);
    }
}