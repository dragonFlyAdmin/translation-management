<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Routing\Router;

class exportController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
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
        $this->manager->actions()->export('*');
        
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
        $this->manager->actions()->export($group);
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->meta()->loadAmountChangedRecords()
        ]);
    }
}