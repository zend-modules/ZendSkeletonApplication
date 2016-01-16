<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\HelperPluginManager as ViewHelperPluginManager;
use Zend\View\Model\ViewModel;

class ContactController extends AbstractActionController
{
    public function indexAction()
    {
        $services = $this->getServiceLocator();
        $viewHelperManager = $services->get('ViewHelperManager');
        if ($viewHelperManager instanceof ViewHelperPluginManager) {
            // htmlTag view helper
            if ( $viewHelperManager->has('htmlTag') ) {
                $htmlTag = $viewHelperManager->get('htmlTag');
                $htmlTag->setAttribute('itemtype', 'http://schema.org/ContactPage');
            }
        }

        return new ViewModel();
    }
}