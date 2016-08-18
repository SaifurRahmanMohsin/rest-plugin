<?php namespace Mohsin\Rest\Behaviors;

use Db;
use Str;
use Backend\Classes\ControllerBehavior;

/**
 * Rest Controller Behavior
 *
 * Adds REST features for working with backend models.
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['Mohsin.Rest.Behaviors.RestController'];
 *
 * @author Saifur Rahman Mohsin
 */
class RestController extends ControllerBehavior
{
    /**
     * @var Model The child controller that implements the behavior.
     */
    protected $controller;

    /**
     * @var Model The initialized model used by the rest controller.
     */
    protected $model;

    /**
     * @var String The prefix for verb methods.
     */
    protected $prefix = '';

    /**
     * {@inheritDoc}
     */
    protected $requiredProperties = ['restConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     * - list: List column definitions
     */
    protected $requiredConfig = ['modelClass', 'allowedActions'];

    /**
     * Behavior constructor
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);
        $this -> controller = $controller;

        /*
         * Build configuration
         */
        $this->config = $this->makeConfig($controller->restConfig, $this->requiredConfig);
        $this->config->modelClass = Str::normalizeClassName($this->config->modelClass);

        if(isset($this->config->prefix))
          $this->prefix = $this->config->prefix;
    }

    public function index()
    {
        $model = $this->createModel();
        return response()->json([
            'response' => $model->all(),
        ], 200);
    }

    /**
     * Internal method, prepare the model object
     * @return Model
     */
    protected function createModel()
    {
        $class = $this->config->modelClass;
        return new $class();
    }

    /* Functions to allow RESTful actions */
    public static function getAfterFilters() {return [];}
    public static function getBeforeFilters() {return [];}
    public static function getMiddleware() {return [];}
    public function callAction($method, $parameters=false) {
      $action = Str::camel($this -> prefix . ' ' . $method);
      if (method_exists($this->controller, $action) && is_callable(array($this->controller, $action)) && in_array($method, $this->config->allowedActions))
      {
        return call_user_func_array(array($this->controller, $action), $parameters);
      }
      else if (method_exists($this, $action) && is_callable(array($this, $action)) && in_array($method, $this->config->allowedActions))
      {
        return call_user_func_array(array($this, $action), $parameters);
      }
      else
      {
        return response()->json([
            'response' => 'Not Found',
        ], 404);
      }
    }
}
