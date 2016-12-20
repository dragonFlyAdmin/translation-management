<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Routing\Router;

class exportController
{
    /** @var Managers\Manager */
    protected $manager;
    
    public function __construct(Router $router)
    {
        $this->manager = (new Managers())->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Export all translations groups.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExport($manager)
    {
        $groups = $this->manager->store()->groups();
        
        foreach($groups as $id => $group)
        {
            if($id == '')
                continue;
            
            $this->manager->local()->export($group['value'], $this->manager->store()->translations($group['value'])[0]);
        }
        
        return response()->json([
            'status' => 'success'
        ]);
    }
    
    /**
     * Export a specific translation group.
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExportGroup($manager, $group)
    {
        $this->manager->local()->export($group, $this->manager->store()->translations($group)[0]);
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->store()->amountChangedRecords()
        ]);
    }
}