<?php

namespace Kalnoy\Cruddy;

use Illuminate\Config\Repository as Config;
use Illuminate\Events\Dispatcher;
use Kalnoy\Cruddy\Schema\Fields\Factory as FieldFactory;
use Kalnoy\Cruddy\Schema\Columns\Factory as ColumnFactory;
use Kalnoy\Cruddy\Service\Permissions\PermissionsManager;
use RuntimeException;

/**
 * Cruddy environment.
 *
 * @since 1.0.0
 */
class Environment {

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * The entities repository.
     *
     * @var Repository
     */
    protected $entities;

    /**
     * @var Service\Permissions\PermissionsManager
     */
    protected $permissions;

    /**
     * @var Lang
     */
    protected $lang;

    /**
     * Event dispatcher.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * @param Config             $config
     * @param Repository         $entities
     * @param FieldFactory       $fields
     * @param ColumnFactory      $columns
     * @param PermissionsManager $permissions
     * @param Lang               $lang
     * @param Dispatcher         $dispatcher
     */
    public function __construct(
        Config $config, Repository $entities, PermissionsManager $permissions, Lang $lang, Dispatcher $dispatcher)
    {
        $this->config = $config;
        $this->entities = $entities;
        $this->permissions = $permissions;
        $this->lang = $lang;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Resolve an entity.
     *
     * @param $id
     *
     * @return Entity
     */
    public function entity($id)
    {
        return $this->entities->resolve($id);
    }

    /**
     * Get configuration option from cruddy configuration file.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->config->get("cruddy::{$key}", $default);
    }

    /**
     * Translate a key.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return string
     */
    public function translate($key, $default = null)
    {
        return $this->lang->translate($key, $default);
    }

    /**
     * Find a field with given id.
     *
     * The full field id consists of two parts: the entity id and the field id.
     * I.e. `users.password`.
     *
     * @param string $id
     *
     * @throws RuntimeException
     *
     * @return Schema\Fields\BaseField
     */
    public function field($id)
    {
        list($entityId, $fieldId) = explode('.', $id, 2);

        $entity = $this->entities->resolve($entityId);
        $field = $entity->getFields()->get($fieldId);

        if ( ! $field)
        {
            throw new RuntimeException("The field [{$fieldId}] of [{$entityId}] entity is not found.");
        }

        return $field;
    }

    /**
     * Get whether the action for an entity is permitted.
     *
     * @param string $action
     * @param Entity $entity
     *
     * @return bool
     */
    public function isPermitted($action, Entity $entity)
    {
        return $this->permissions->isPermitted($action, $entity);
    }

    /**
     * Permissions object.
     *
     * @return Service\Permissions\PermissionsManager
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Get entity repository.
     *
     * @return Repository
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Resolve and convert all entities to array.
     *
     * @return array
     */
    public function schema()
    {
        return array_map(function (Entity $entity)
        {
            return $entity->toArray();

        }, $this->entities->resolveAll());
    }

    /**
     * Get permissions for every entity.
     *
     * @return array
     */
    public function permissions()
    {
        $data = [];

        foreach ($this->entities->resolveAll() as $entity)
        {
            $data[$entity->getId()] = $entity->getPermissions();
        }

        return $data;
    }

    /**
     * @return array
     */
    public function data()
    {
        return [
            'locale' => $this->config->get('app.locale'),
            'brandName' => Helpers::tryTranslate($this->config('brand')),
            'uri' => $this->config('uri'),
            'ace_theme' => $this->config('ace_theme', 'chrome'),
            'entities' => $this->entities->available(),
            'lang' => $this->lang->ui(),
            'permissions' => $this->permissions(),
        ];
    }

    /**
     * Get event dispatcher.
     *
     * @return \Illuminate\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Register saving event handler.
     *
     * @param string $id
     * @param mixed $callback
     *
     * @return void
     */
    public function saving($id, $callback)
    {
        Entity::saving($id, $callback);
    }

    /**
     * Register saved event handler.
     *
     * @param string $id
     * @param mixed $callback
     *
     * @return void
     */
    public function saved($id, $callback)
    {
        Entity::saved($id, $callback);
    }

}