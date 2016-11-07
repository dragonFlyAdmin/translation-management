<?php namespace DragonFly\TranslationManager;

class Managers
{
    public function make($manager='Laravel')
    {
        $managerClass = '\DragonFly\TranslationManager\Managers\\'.ucfirst($manager);
        return app($managerClass);
    }
    
    public function managers()
    {
        $managers = ['laravel'];
        
        $thirdParty = config('translations.external');
        
        foreach ($thirdParty as $name => $manager)
        {
            if(array_get($manager, 'enabled', false))
            {
                $managers[] = $name;
            }
        }
        
        return $managers;
    }
}
