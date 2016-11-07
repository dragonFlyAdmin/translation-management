<?php

namespace DragonFly\TranslationManager\Managers\Spatie;


use DragonFly\TranslationManager\Managers\Template\Manager as BaseManager;

class Manager extends BaseManager
{
    public $managerName = 'dimsav';
    
    public $localGroups = [];
    
    /**
     * Return the actions tha can be performed on the stored translations.
     *
     * @return \DragonFly\TranslationManager\Managers\Template\Actions
     */
    public function actions()
    {
        return new Actions($this);
    }
    
    public function localGroups()
    {
        if(count($this->localGroups) == 0)
        {
            // Loop over the model classes
            foreach($this->managerConfig['models'] as $modelClass)
            {
                // Initialise the model, retrieve the translation slug and register it.
                $model = new $modelClass;
                $this->localGroups[$model->getTranslationSlug] = $modelClass;
            }
        }
        
        return $this->localGroups;
    }
}