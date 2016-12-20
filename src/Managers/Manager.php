<?php

namespace DragonFly\TranslationManager\Managers;


use DragonFly\TranslationManager\Managers\Contracts\CanCreateLocal;
use DragonFly\TranslationManager\Managers\Contracts\CanScan;
use DragonFly\TranslationManager\Managers\Contracts\StringHasMultipleKeys;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

class Manager
{
    /** @var \Illuminate\Foundation\Application */
    public $app;
    /** @var \Illuminate\Filesystem\Filesystem */
    public $files;
    /** @var \Illuminate\Events\Dispatcher */
    public $events;
    /** @var array Main translations manager config */
    public $config;
    /** @var array Config values specific for this manager */
    public $managerConfig;
    
    public $managerName = false;
    /**
     * @var Repository
     */
    public $repository;
    
    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translations'];
    }
    
    /**
     * Setup the manager config and instance
     *
     * @param $managerName
     *
     * @return $this
     */
    public function setup($managerName)
    {
        $this->managerName = $managerName;
        $this->managerConfig = $this->config['drivers'][$this->managerName];
        
        // Init the repo class so we can handle 'local' in/output
        $repo = '\DragonFly\TranslationManager\\' . ucfirst($managerName) . 'Repository';
        
        $this->repository = $this->app->make($repo, ['manager' => $this]);
        
        return $this;
    }
    
    /**
     * Returns the class to access/process data to/from the driver.
     *
     * @return \DragonFly\TranslationManager\Managers\Local
     */
    public function local()
    {
        return new Local($this);
    }
    
    /**
     * Return the actions tha can be performed on the stored translations.
     *
     * @return \DragonFly\TranslationManager\Managers\Store
     */
    public function store()
    {
        return new Store($this);
    }
    
    /**
     * Get this manager's config.
     *
     * @param null|string $key
     *
     * @return array|mixed
     */
    public function getConfig($key = null)
    {
        return ( $key == null ) ? $this->managerConfig : $this->managerConfig[$key];
    }
    
    /**
     * Is it possible that translations can have multiple keys?
     * @return bool
     */
    public function canHaveMultipleKeys()
    {
        return is_a($this->repository, StringHasMultipleKeys::class);
    }
    
    /**
     * Does the manager allow for scanning?
     * @return bool
     */
    public function canScan()
    {
        return is_a($this->repository, CanScan::class);
    }
    
    /**
     * Does the manager allow for creating new translation strings?
     * @return bool
     */
    public function canCreateLocal()
    {
        return is_a($this->repository, CanCreateLocal::class);
    }
}