<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Routing\Router;

class ImportController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
    protected $manager;
    
    public function __construct(Router $router)
    {
        $this->manager = (new Managers())->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Scan file located in the app folder for translations used in functions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScan($manager)
    {
        if(!$this->manager->can('scan'))
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => $this->manager->managerName . ' does not offer "scan".',
            ]);
        }
        
        $importedKeys = $this->manager->actions()->scan();
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'scanned' => $importedKeys,
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales()
        ]);
    }
    
    /**
     * Import translations that don't have their key/locale stored on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppend($manager)
    {
        $importedKeys = $this->manager->actions()->import('*', false);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'imported' => $importedKeys,
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales()
        ]);
    }
    
    /**
     * Import translations that don't have their key/locale stored on record (for a specific group).
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppendGroup($manager, $group)
    {
        $importedKeys = $this->manager->actions()->import($group, false);
        
        return response()->json([
            'status' => 'success',
            'imported' => $importedKeys,
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales()
        ]);
    }
    
    /**
     * Import translations and overwrite the ones we have on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplace($manager)
    {
        $importedKeys = $this->manager->actions()->import('*', true);
        
        return response()->json([
            'status' => 'success',
            'imported' => $importedKeys,
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales(),
            'changed' => $this->manager->meta()->loadAmountChangedRecords()
        ]);
    }
    
    /**
     * Import translations and overwrite the ones we have on record (for a specific group).
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplaceGroup($manager, $group)
    {
        $importedKeys = $this->manager->actions()->import($group, true);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'imported' => $importedKeys,
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales(),
            'changed' => $this->manager->meta()->loadAmountChangedRecords()
        ]);
    }
}