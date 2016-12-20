<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use DragonFly\TranslationManager\Models\TranslationString;
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
        $meta = [];
        
        foreach($managers as $name)
        {
            $manager = $this->loader->make($name);
            $manager->store()->clean();
            
            $meta[$name] = [
                'groups' => $manager->store()->groups(),
                'locales' => $manager->store()->locales(),
                'records' => $manager->store()->uniqueKeysCount(),
                'changed' => $manager->store()->amountChangedRecords()
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'meta' => $meta
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
            $this->loader->make($name)->store()->truncate();
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
        if(!$this->manager->config['features']['create_locales'])
        {
            return response()->json([
                'status' => 'impossible'
            ]);
        }
    
        $newLocale = $request->input('locale');
    
        // Check if it already exists
        if (TranslationString::where('locale', $newLocale)->count() > 0)
        {
            return response()->json([
                'status' => 'on_record'
            ]);
        }
    
        $addedKeys = 0;
    
        // Loop over the unique keys and add the new locale with a null value
        $this->manager
            ->store()
            ->uniqueKeys()
            ->each(function ($string) use ($newLocale, &$addedKeys)
            {
                TranslationString::create([
                    'group' => $string->group,
                    'key' => $string->key,
                    'locale' => $newLocale,
                    'manager' => $this->manager->managerName,
                    'value' => ['value' => null],
                    'status' => TranslationString::STATUS_CHANGED,
                ]);
            
                $addedKeys++;
            });
        
        return response()->json([
            'status' => 'success',
            'added' => $addedKeys
        ]);
    }
}