<?php

namespace DragonFly\TranslationManager\Managers\Template;


use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

abstract class Manager
{
    /** @var \Illuminate\Foundation\Application */
    public $app;
    /** @var \Illuminate\Filesystem\Filesystem */
    public $files;
    /** @var \Illuminate\Events\Dispatcher */
    public $events;
    /** @var array Main translations manager config*/
    public $config;
    /** @var array Config values specific for this manager*/
    protected $managerConfig;
    
    public $managerName = false;
    
    protected $features = [
        'locale.create' => false,
        'string.create' => false,
        'scan' => false,
    ];
    
    
    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translations'];
        
        $this->managerConfig = ($this->managerName == 'laravel') ? $this->config : $this->config['external'][$this->managerName];
    }
    
    /**
     * Check if the manager is able to perform this feature.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function can($feature)
    {
        return array_get($this->features, $feature, false);
    }
    
    /**
     * Return the meta helper.
     *
     * @return \DragonFly\TranslationManager\Managers\Template\Meta
     */
    public function meta()
    {
        return new BaseMeta($this);
    }
    
    /**
     * Return the actions tha can be performed on the stored translations.
     *
     * @return \DragonFly\TranslationManager\Managers\Template\Actions
     */
    abstract public function actions();
    
    /**
     * Get this manager's config.
     *
     * @param null|string   $key
     *
     * @return array|mixed
     */
    public function getConfig($key = null)
    {
        return ( $key == null ) ? $this->managerConfig : $this->managerConfig[$key];
    }
}