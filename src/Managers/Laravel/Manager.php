<?php

namespace DragonFly\TranslationManager\Managers\Laravel;


use DragonFly\TranslationManager\Managers\Template\Manager as BaseManager;

class Manager extends BaseManager
{
    public $managerName = 'laravel';
    
    protected $features = [
        'locale.create' => true,
        'string.create' => true,
        'scan' => true,
    ];
    
    /**
     * Return the meta helper.
     *
     * @return \DragonFly\TranslationManager\Managers\Laravel\Meta
     */
    public function meta()
    {
        return new Meta($this);
    }
    
    /**
     * Return the actions tha can be performed on the stored translations.
     *
     * @return \DragonFly\TranslationManager\Managers\Laravel\Actions
     */
    public function actions()
    {
        return new Actions($this);
    }
}