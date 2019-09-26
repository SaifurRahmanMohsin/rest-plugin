<?php namespace Mohsin\Rest\Classes;

use ApplicationException;
use Mohsin\Rest\Models\Settings;
use System\Classes\PluginManager;

/**
 * Manages all the API nodes.
 *
 * @package Mohsin.Rest
 * @author Saifur Rahman Mohsin
 */
class ApiManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var collection Cache of the registered API nodes.
     */
    protected $nodes = [];

    /**
     * @var October\Rain\Router\CoreRouter
     */
    protected $router;

    /**
     * @var System\Classes\PluginManager
     */
    protected $pluginManager;

    /**
     * @var API prefix
     * TODO: Replace with customizable value.
     */
    protected $prefix;

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->pluginManager = PluginManager::instance();
        $this->router = app()->router;

        $this->prefix = Settings::get('prefix', 'api/v1/');

        if (empty($nodes)) {
            $this->loadApiNodes();
        }
    }

    /**
     * Loads the API nodes from plugins
     * @return void
     */
    protected function loadApiNodes()
    {
        $plugins = $this->pluginManager->getPlugins();

        foreach ($plugins as $id => $plugin) {
            if (!method_exists($plugin, 'registerNodes')) {
                continue;
            }

            $nodes = $plugin->registerNodes();
            if (!is_array($nodes)) {
                continue;
            }

            $this->registerNodes($id, $nodes);
        }
    }

    /**
     * Registers the API nodes exposed by a plugin.
     * The argument is an array of the nodes and their configurations.
     * @param string $owner Specifies the menu items owner plugin or module in the format Author.Plugin.
     * @param array $nodes An array of the nodes the plugin exposes.
     * @return void
     */
    public function registerNodes($owner, array $nodes)
    {
        foreach ($nodes as $path => $config) {
            $node = (object) [
                'owner'  => $owner,
                'path'   => $path,
                'config' => $config,
            ];

            $this->registerNodesWithRouter($path, (object) $config);
            $this->nodes[$path] = $node;
        }
    }

    public function registerNodesWithRouter($node, object $config)
    {
        $controller = explode('@', $config->controller);
        $path = $this->prefix . $node;
        $options = (array) $config;

        if (count($controller) == 1) {
            $this->router->apiResource($path, $controller, $options);
        } else {
            switch ($config->action) {
                case 'index':
                case 'create':
                case 'show':
                case 'edit':
                    $this->router->get($path, $config->controller);
                    break;
                case 'store':
                    $this->router->post($path, $config->controller);
                    break;
                case 'update':
                    $this->router->put($path, $config->controller);
                    $this->router->patch($path, $config->controller);
                    break;
                case 'destroy':
                    $this->router->delete($path, $config->controller);
                    break;
                default:
                    throw new ApplicationException(sprintf('Invalid action is route %s', $path));
            }
        }
    }
}
