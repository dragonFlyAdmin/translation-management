<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Manager;
use Illuminate\Http\Request;

class ActionsController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
    protected $manager;
    
    public function __construct(Router $router)
    {
        $this->manager = (new Manager())->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Remove all translations keys whose value is 'null'.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClean($manager)
    {
        $this->manager->actions()->clean();
        
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
        $this->manager->actions()->truncate();
        
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