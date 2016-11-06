<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Manager;

class exportController
{
    /** @var \DragonFly\TranslationManager\Managers\BaseManager */
    protected $manager;
    
    public function __construct()
    {
        $this->manager = (new Manager())->make();
    }
    
    /**
     * Export all translations groups.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExport()
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
    public function getExportGroup($group)
    {
        $this->manager->actions()->export($group);
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->meta()->loadAmountChangedRecords()
        ]);
    }
}