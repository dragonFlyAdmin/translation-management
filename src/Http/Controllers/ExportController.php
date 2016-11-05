<?php

namespace DragonFly\TranslationManager\Http\Controllers;



use DragonFly\TranslationManager\LaravelStringManager;

class exportController
{
    /** @var \DragonFly\TranslationManager\LaravelStringManager */
    protected $manager;
    
    public function __construct(LaravelStringManager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Export all translations groups.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExport()
    {
        $this->manager->exportTranslations('*');
        
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
        $this->manager->exportTranslations($group);
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->loadAmountChangedRecords()
        ]);
    }
}