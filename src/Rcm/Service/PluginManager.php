<?php
/**
 * Rcm Plugin Manager
 *
 * This file contains the class definition for the Plugin Manager
 *
 * PHP version 5.3
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */

namespace Rcm\Service;

use Doctrine\ORM\EntityManagerInterface;
use Rcm\Entity\PluginInstance;
use Rcm\Exception\InvalidPluginException;
use Rcm\Exception\PluginInstanceNotFoundException;
use Rcm\Exception\PluginReturnedResponseException;
use Rcm\Exception\RuntimeException;
use Rcm\Http\Response;
use Rcm\Plugin\PluginInterface;
use Zend\Cache\Storage\StorageInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;
use Zend\View\Helper\Placeholder\Container;
use Zend\View\Renderer\PhpRenderer;

/**
 * Rcm Plugin Manager
 *
 * Rcm plugin Manager.  This class handles everything about a CMS plugin.  Please
 * note that this handles the plugins directly and does not do anything in regards
 * to where the plugin renders on the page or in the container.  Positional
 * information is stored within a plugin wrapper and not within the plugin itself.
 * Wrapping the plugin with a positional wrapper helps to make plugins and plugin
 * instances reusable through out the site.
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 *
 * @SuppressWarnings(PHPMD)
 * @todo      - See if we can reduce the amount of dependencies.
 */
class PluginManager
{
    /** @var \Zend\ServiceManager\ServiceLocatorInterface */
    protected $serviceManager;

    /** @var \Zend\Stdlib\RequestInterface */
    protected $request;

    /** @var \Zend\View\Renderer\PhpRenderer */
    protected $renderer;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var array */
    protected $config;

    /** @var \Zend\Cache\Storage\StorageInterface */
    protected $cache;

    /**
     * Constructor
     *
     * @param EntityManagerInterface  $entityManager  Doctrine Entity Manager
     * @param array                   $config         Config Array
     * @param ServiceLocatorInterface $serviceManager Zend Service Manager
     * @param PhpRenderer             $renderer       Zend Renderer
     * @param RequestInterface        $request        Zend Request Object
     * @param StorageInterface        $cache          Zend Cache Manager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Array                   $config,
        ServiceLocatorInterface $serviceManager,
        PhpRenderer $renderer,
        RequestInterface $request,
        StorageInterface $cache
    ) {
        $this->serviceManager = $serviceManager;
        $this->request = $request;
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * Get a new plugin instance.
     *
     * @param string $pluginName Plugin Name
     *
     * @return array
     */
    public function getNewEntity($pluginName)
    {
        $instanceConfig = $this->getDefaultInstanceConfig($pluginName);

        $viewData = $this->getPluginViewData($pluginName, -1, $instanceConfig);

        return $viewData;
    }

    /**
     * Get a plugin by instance Id
     *
     * @param integer $pluginInstanceId Plugin Instance Id
     *
     * @return array|mixed
     * @throws \Rcm\Exception\PluginInstanceNotFoundException
     */
    public function getPluginByInstanceId($pluginInstanceId)
    {
        $cacheId = 'rcmPluginInstance_' . $pluginInstanceId;

        if ($this->cache->hasItem($cacheId)) {
            $return = $this->cache->getItem($cacheId);
            $return['fromCache'] = true;

            return $return;
        }

        $pluginInstance = $this->getInstanceEntity($pluginInstanceId);

        if (empty($pluginInstance)) {
            throw new PluginInstanceNotFoundException(
                'Plugin for instance id ' . $pluginInstanceId . ' not found.'
            );
        }

        $instanceConfig = $this->getInstanceConfigFromEntity($pluginInstance);

        $return = $this->getPluginViewData(
            $pluginInstance->getPlugin(),
            $pluginInstanceId,
            $instanceConfig
        );

        if ($pluginInstance->isSiteWide()) {

            $return['siteWide'] = true;

            $displayName = $pluginInstance->getDisplayName();

            if (!empty($displayName)) {
                $return['displayName'] = $displayName;
            }
        }

        $return['md5'] = $pluginInstance->getMd5();

        if ($return['canCache']) {
            $this->cache->setItem($cacheId, $return);
        }

        return $return;
    }

    /**
     * Get a plugin instance rendered view.
     *
     * @param string  $pluginName           Plugin name
     * @param integer $pluginInstanceId     Plugin Instance Id
     * @param array   $pluginInstanceConfig Plugin Instance Config
     *
     * @return array
     * @throws \Rcm\Exception\InvalidPluginException
     * @throws \Rcm\Exception\PluginReturnedResponseException
     */
    public function getPluginViewData(
        $pluginName,
        $pluginInstanceId,
        $pluginInstanceConfig
    ) {

        /** @var \Rcm\Plugin\PluginInterface $controller */
        $controller = $this->getPluginController($pluginName);

        if (!is_a($controller, '\Rcm\Plugin\PluginInterface')) {
            throw new InvalidPluginException(
                'Plugin ' . $controller . ' must implement the PluginInterface'
            );
        }

        $reflectionRequest = new \ReflectionProperty($controller, 'request');
        $reflectionRequest->setAccessible(true);
        $reflectionRequest->setValue($controller, $this->request);

        $reflectionResponse = new \ReflectionProperty($controller, 'response');
        $reflectionResponse->setAccessible(true);
        $reflectionResponse->setValue($controller, new Response());

        $viewModel = $controller->renderInstance(
            $pluginInstanceId,
            $pluginInstanceConfig
        );

        if ($viewModel instanceof ResponseInterface) {
            $exception = new PluginReturnedResponseException(
                'Plugin returned response instead of View Model'
            );

            $exception->setResponse($viewModel);

            throw $exception;
        }

        /** @var \Zend\View\Helper\Headlink $headlink */
        $headlink = $this->renderer->plugin('headlink');

        /** @var \Zend\View\Helper\HeadScript $headScript */
        $headScript = $this->renderer->plugin('headscript');

        $oldContainer = $headlink->getContainer();
        $linkContainer = new Container();
        $headlink->setContainer($linkContainer);

        $oldScriptContainer = $headScript->getContainer();
        $headScriptContainer = new Container();
        $headScript->setContainer($headScriptContainer);

        $html = $this->renderer->render($viewModel);
        $css = $headlink->getContainer()->getArrayCopy();
        $script = $headScript->getContainer()->getArrayCopy();

        $return = array(
            'html' => $html,
            'css' => $this->getContainerSrc($css),
            'js' => $this->getContainerSrc($script),
            'editJs' => '',
            'editCss' => '',
            'displayName' => '',
            'tooltip' => '',
            'icon' => '',
            'siteWide' => false,
            'md5' => '',
            'fromCache' => false,
            'canCache' => true,
            'pluginName' => $pluginName,
            'pluginInstanceId' => $pluginInstanceId,
        );

        if (isset($this->config['rcmPlugin'][$pluginName]['editJs'])) {
            $return['editJs']
                = $this->config['rcmPlugin'][$pluginName]['editJs'];
        }

        if (isset($this->config['rcmPlugin'][$pluginName]['editCss'])) {
            $return['editCss']
                = $this->config['rcmPlugin'][$pluginName]['editCss'];
        }

        if (isset($this->config['rcmPlugin'][$pluginName]['display'])) {
            $return['displayName']
                = $this->config['rcmPlugin'][$pluginName]['display'];
        }

        if (isset($this->config['rcmPlugin'][$pluginName]['tooltip'])) {
            $return['tooltip']
                = $this->config['rcmPlugin'][$pluginName]['tooltip'];
        }

        if (isset($this->config['rcmPlugin'][$pluginName]['icon'])) {
            $return['icon'] = $this->config['rcmPlugin'][$pluginName]['icon'];
        }

        if (isset($this->config['rcmPlugin'][$pluginName]['canCache'])) {
            $return['canCache']
                = $this->config['rcmPlugin'][$pluginName]['canCache'];
        }

        $headlink->setContainer($oldContainer);
        $headScript->setContainer($oldScriptContainer);

        return $return;

    }

    /**
     * Save a plugin instance
     *
     * @param integer $pluginInstanceId Current Instance Id
     * @param mixed   $saveData         Plugin Data to Save
     *
     * @return PluginInstance New saved plugin instance
     */
    public function savePlugin($pluginInstanceId, $saveData)
    {
        $pluginInstance = $this->getInstanceEntity($pluginInstanceId);

        if ($pluginInstance->getMd5() == md5(serialize($saveData))) {
            return $pluginInstance;
        }

        $newPluginInstance = $this->saveNewInstance(
            $pluginInstance->getPlugin(),
            $saveData,
            $pluginInstance->isSiteWide(),
            $pluginInstance->getDisplayName()
        );

        return $newPluginInstance;
    }

    /**
     * Delete a plugin instance.  This should generally never be used unless the
     * container, page, or site is being deleted.  And only if the plugin instance
     * does not belong to a site wide plugin unless you are deleting the entire
     * site.
     *
     * @param integer $pluginInstanceId Instance Id
     *
     * @return void
     * @throws \Rcm\Exception\PluginInstanceNotFoundException
     */
    public function deletePluginInstance($pluginInstanceId)
    {
        $pluginInstanceEntity = $this->getInstanceEntity($pluginInstanceId);

        if (empty($pluginInstanceEntity)) {
            throw new PluginInstanceNotFoundException(
                'No plugin found to delete'
            );
        }

        /** @var \Rcm\Plugin\PluginInterface $controller */
        $controller = $this->getPluginController(
            $pluginInstanceEntity->getPlugin()
        );
        $controller->deleteInstance($pluginInstanceEntity->getInstanceId());

        $this->entityManager->remove($pluginInstanceEntity);
        $this->entityManager->flush();
    }

    /**
     * Save a new plugin instance
     *
     * @param string      $pluginName  Plugin name
     * @param array       $saveData    Save Data
     * @param bool        $siteWide    Site Wide marker
     * @param null|string $displayName Display name for site wide plugins.  Required
     *                                 for site wide plugin instances.
     *
     * @return PluginInstance
     */
    public function saveNewInstance(
        $pluginName,
        $saveData,
        $siteWide = false,
        $displayName = null
    ) {
        $pluginInstance = $this->getNewPluginInstanceEntity($pluginName);

        if ($siteWide) {
            $pluginInstance->setSiteWide();

            if (!empty($displayName)) {
                $pluginInstance->setDisplayName($displayName);
            }
        }

        $pluginInstance->setMd5(md5(serialize($saveData)));

        $this->entityManager->persist($pluginInstance);
        $this->entityManager->flush();

        /** @var \Rcm\Plugin\PluginInterface $controller */
        $controller = $this->getPluginController($pluginName);

        $controller->saveInstance($pluginInstance->getInstanceId(), $saveData);

        return $pluginInstance;
    }

    /**
     * Get a new Plugin Entity
     *
     * @param string $pluginName Plugin Name
     *
     * @return PluginInstance
     */
    public function getNewPluginInstanceEntity($pluginName)
    {
        $pluginInstance = new PluginInstance();
        $pluginInstance->setPlugin($pluginName);

        if (isset($this->config['rcmPlugin'][$pluginName]['display'])) {
            $pluginInstance->setDisplayName(
                $this->config['rcmPlugin'][$pluginName]['display']
            );
        }

        $this->entityManager->persist($pluginInstance);
        $this->entityManager->flush();

        return $pluginInstance;
    }

    /**
     * Get a plugin containers CSS and Javascript from either the headlink or
     * head script
     *
     * @param array $container Zend Framework View Helper array copy to serialize
     *
     * @return array
     */
    public function getContainerSrc($container)
    {
        if (empty($container) || !is_array($container)) {
            return array();
        }

        $return = array();

        foreach ($container as &$item) {

            if ($item->type == 'text/css') {
                $return[] = serialize($item);
            } elseif ($item->type == 'text/javascript') {
                $return[] = serialize($item);
            }
        }

        return $return;
    }

    /**
     * Get an instantiated plugin controller
     *
     * @param string $pluginName Plugin Name
     *
     * @return PluginInterface
     * @throws \Rcm\Exception\InvalidPluginException
     * @throws \Rcm\Exception\RuntimeException
     */
    public function getPluginController($pluginName)
    {

        /*
         * Deprecated.  All controllers should come from the controller manager
         * now and not the service manager.
         *
         * @todo Remove if statement once plugins have been converted.
         */
        if ($this->serviceManager->has($pluginName)) {
            $serviceManager = $this->serviceManager;
        } else {
            $serviceManager = $this->serviceManager->get('ControllerLoader');
        }

        if (!$serviceManager->has($pluginName)) {
            throw new InvalidPluginException(
                "Plugin $pluginName is not loaded or configured. Check
            config/application.config.php"
            );
        }

        //Load the plugin controller
        try {
            $pluginController = $serviceManager->get($pluginName);
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Unable to get instance of plugin: ' . $pluginName,
                1,
                $e
            );
        }

        //Plugin controllers must implement this interface
        if (!$pluginController instanceof PluginInterface) {
            throw new InvalidPluginException(
                'Class "' . get_class($pluginController) . '" for plugin "'
                . $pluginName . '" does not implement '
                . '\Rcm\Plugin\PluginInterface'
            );
        }

        return $pluginController;
    }

    /**
     * Get an Plugin Instance Entity by Instance Id
     *
     * @param integer $pluginInstanceId Plugin Instance Id
     *
     * @return \Rcm\Entity\PluginInstance
     */
    protected function getInstanceEntity($pluginInstanceId)
    {
        return $this->entityManager
            ->getRepository('\Rcm\Entity\PluginInstance')
            ->findOneBy(array('pluginInstanceId' => $pluginInstanceId));
    }

    public function getInstanceConfig($pluginInstanceId)
    {
        $pluginInstance = $this->getInstanceEntity($pluginInstanceId);

        if (empty($pluginInstance)) {
            throw new PluginInstanceNotFoundException(
                'Plugin for instance id ' . $pluginInstanceId . ' not found.'
            );
        }

        return $this->getInstanceConfigFromEntity($pluginInstance);
    }

    /**
     * Get Instance Config From Entity
     *
     * @param PluginInstance $pluginInstance
     *
     * @return array
     */
    protected function getInstanceConfigFromEntity(PluginInstance $pluginInstance)
    {
        //Instance configs less than 0 are default instanc configs
        if ($pluginInstance->getInstanceId() < 0) {
            return $this->getDefaultInstanceConfig($pluginInstance->getPlugin());
        } else {
            $instanceConfig = $pluginInstance->getInstanceConfig();

            if (!is_array($instanceConfig)) {
                $instanceConfig = array();
            }

            //Merge the default and db instance configs. Db overwrites.
            $instanceConfig = $this->mergeConfigArrays(
                $this->getDefaultInstanceConfig($pluginInstance->getPlugin()),
                $instanceConfig
            );
        }

        return $instanceConfig;
    }

    /**
     * Default instance configs are NOT required anymore
     *
     * @param string $pluginName the plugins module name
     *
     * @return array
     */
    protected function getDefaultInstanceConfig($pluginName)
    {
        $pluginConfigs = $this->config['rcmPlugin'];

        $defaultInstanceConfig = array();

        if (array_key_exists('defaultInstanceConfig', $pluginConfigs[$pluginName])) {
            $defaultInstanceConfig =  $pluginConfigs[$pluginName]['defaultInstanceConfig'];
        }

        return $defaultInstanceConfig;
    }

    /**
     * Merge Config Arrays
     *
     * @param $default
     * @param $changes
     *
     * @return mixed
     */
    protected function mergeConfigArrays($default, $changes)
    {

        if (empty($default)) {
            return $changes;
        }

        if (empty($changes)) {
            return $default;
        }

        foreach ($changes as $key => &$value) {
            if (is_array($value)) {
                if (isset($value['0'])) {
                    /*
                     * Numeric arrays ignore default values because of the
                     * "more in default that on production" issue
                     */
                    $default[$key] = $changes[$key];
                } else {
                    if (isset($default[$key])) {
                        $default[$key] = self::mergeConfigArrays(
                            $default[$key],
                            $changes[$key]
                        );
                    } else {
                        $default[$key] = $changes[$key];
                    }
                }
            } else {
                $default[$key] = $changes[$key];
            }
        }
        return $default;
    }
}