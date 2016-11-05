<?php

namespace DragonFly\TranslationManager\Http\Controllers;



use DragonFly\TranslationManager\LaravelStringManager;

class ImportController
{
    /** @var \DragonFly\TranslationManager\LaravelStringManager */
    protected $manager;
    
    public function __construct(LaravelStringManager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Scan file located in the app folder for translations used in functions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScan()
    {
        $importedKeys = $this->manager->scanForTranslations();
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->uniqueKeys()->count(),
            'scanned' => $importedKeys,
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales()
        ]);
    }
    
    /**
     * Import translations that don't have their key/locale stored on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppend()
    {
        $importedKeys = $this->manager->importTranslations(false);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->uniqueKeys()->count(),
            'imported' => $importedKeys,
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales()
        ]);
    }
    
    /**
     * Import translations that don't have their key/locale stored on record (for a specific group).
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppendGroup($group)
    {
        $importedKeys = $this->manager->importGroupTranslations($group, false);
        
        return response()->json([
            'status' => 'success',
            'imported' => $importedKeys,
            'records' => $this->manager->uniqueKeys()->count(),
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales()
        ]);
    }
    
    /**
     * Import translations and overwrite the ones we have on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplace()
    {
        $importedKeys = $this->manager->importTranslations(true);
        
        return response()->json([
            'status' => 'success',
            'imported' => $importedKeys,
            'records' => $this->manager->uniqueKeys()->count(),
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales(),
            'changed' => $this->manager->loadAmountChangedRecords()
        ]);
    }
    
    /**
     * Import translations and overwrite the ones we have on record (for a specific group).
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplaceGroup($group)
    {
        $importedKeys = $this->manager->importGroupTranslations($group, true);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->uniqueKeys()->count(),
            'imported' => $importedKeys,
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales(),
            'changed' => $this->manager->loadAmountChangedRecords()
        ]);
    }
}