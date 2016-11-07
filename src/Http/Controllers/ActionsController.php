<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class ActionsController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
    protected $manager;
    
    /** @var \DragonFly\TranslationManager\Managers */
    protected $loader;
    
    public function __construct(Router $router)
    {
        $this->loader = new Managers();
        $this->manager = $this->loader->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Remove all translations keys whose value is 'null'.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClean($manager)
    {
        $managers = $this->loader->managers();
        
        foreach($managers as $name)
        {
            $this->loader->make($name)->actions()->clean();
        }
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales(),
            'changed' => $this->manager->meta()->loadAmountChangedRecords()
        ]);
    }
    
    /**
     * Remove all translations from the database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTruncate($manager)
    {
        $managers = $this->loader->managers();
    
        foreach($managers as $name)
        {
            $this->loader->make($name)->actions()->truncate();
        }
        
        return response()->json([
            'status' => 'success'
        ]);
    }
    
    /**
     * Adds the new locale to all unique keys in the DB.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCreateLocale(Request $request,$manager)
    {
        // Check if the manager supports this feature
        if(!$this->manager->can('locale.create'))
        {
            return response()->json([
                'status' => 'impossible'
            ]);
        }
    
        $newLocale = $request->input('locale');
        $createdLocaleKeys = $this->manager->actions()->createLocale($newLocale);
        
        // Check if it performed
        if($createdLocaleKeys === false)
        {
            return response()->json([
                'status' => 'on_record'
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'added' => $createdLocaleKeys
        ]);
    }
}