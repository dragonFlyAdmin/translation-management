<?php namespace DragonFly\TranslationManager;

use DragonFly\TranslationManager\Managers\Manager;

class Managers
{
    /**
     * @param string $driver
     *
     * @return Manager
     */
    public function make($driver='Laravel')
    {
        $manager = app('\DragonFly\TranslationManager\Manager');
        
        return $manager->setup($driver);
    }
    
    /**
     * Load all enabled drivers (managers).
     * @return array
     */
    public function managers()
    {
        $managers = [];
        
        $drivers = config('translations.drivers');
        
        foreach ($drivers as $manager => $config)
        {
            if(array_get($config, 'enabled', false))
            {
                $managers[] = $manager;
            }
        }
        
        return $managers;
    }
    
    public function definitions()
    {
        $managers = $this->managers();
        $definitions = [];
        
        foreach($managers as $name)
        {
            $manager = $this->make($name);
            
            $groups = $manager->store()->groups();
            
            $hydrate = [
                'features' => [
                    'scan' => $manager->canScan(),
                    'create' => $manager->canCreateLocal(),
                    'locale.create' => $manager->managerName == 'laravel' && $manager->config['features']['create_locales']
                ],
                'groups' => $groups,
                'stats' => $manager->store()->stats(['changed', 'records', 'locales']),
                'locales' => $manager->store()->locales(),
            ];
            
            $definitions[$name] = $hydrate;
        }
        
        return $definitions;
    }
}
