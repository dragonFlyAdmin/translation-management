<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Routing\Router;

class ImportController
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
     * Scan file located in the app folder for translations used in functions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScan($manager)
    {
        if (!$this->manager->canScan())
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => $this->manager->managerName . ' does not offer "scan".',
            ]);
        }
        
        $importedKeys = $this->manager->local()->scan();
        
        // If missing translations were found, run mass insert
        if(count($importedKeys) > 0)
        {
            TranslationString::insert($importedKeys);
        }
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->store()->uniqueKeysCount(),
            'scanned' => count($importedKeys),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
        ]);
    }
    
    /**
     * Import translations that don't have their key/locale stored on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppend($manager)
    {
        $groups = $this->manager->local()->groups();
        
        foreach ($groups as $key => $group)
        {
            // If the key isn't a number it will be the group name
            if (!is_numeric($key))
            {
                $group = $key;
            }
            
            $translations = $this->manager->local()->translations($group);
            
            $this->importTranslations($group, $translations, false);
        }
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->store()->uniqueKeysCount(),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
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
        $translations = $this->manager->local()->translations($group);
        
        $this->importTranslations($group, $translations, false);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->store()->uniqueKeysCount(),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
        ]);
    }
    
    /**
     * Import translations and overwrite the ones we have on record.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplace($manager)
    {
        $groups = $this->manager->local()->groups();
    
        foreach ($groups as $key => $group)
        {
            // If the key isn't a number it will be the group name
            if (!is_numeric($key))
            {
                $group = $key;
            }
        
            $translations = $this->manager->local()->translations($group);
        
            $this->importTranslations($group, $translations, true);
        }
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->store()->uniqueKeysCount(),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
            'changed' => $this->manager->store()->amountChangedRecords(),
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
        $translations = $this->manager->local()->translations($group);
    
        $this->importTranslations($group, $translations, true);
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->store()->uniqueKeysCount(),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
            'changed' => $this->manager->store()->amountChangedRecords(),
        ]);
    }
    
    /**
     * Import (don't replace, append) translations for every translation manager.
     */
    public function getAll()
    {
        $output = [];
        $managers = $this->loader->managers();
        
        foreach ($managers as $managerName)
        {
            $manager = $this->loader->make($managerName);
            
            $groups = $manager->local()->groups();
            
            foreach ($groups as $key => $group)
            {
                // If the key isn't a number it will be the group name
                if (!is_numeric($key))
                {
                    $group = $key;
                }
                
                $translations = $manager->local()->translations($group);
                
                $this->importTranslations($group, $translations, false);
            }
            
            $output[$manager->managerName] = [
                'records' => $manager->store()->uniqueKeysCount(),
                'groups' => $manager->store()->groups(),
                'locales' => $manager->store()->locales(),
                'changed' => $manager->store()->amountChangedRecords(),
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'managers' => $output,
        ]);
    }
    
    protected function importTranslations($group, $translations, $replace = false)
    {
        foreach ($translations as $locale => $keys)
        {
            foreach ($keys as $key => $string)
            {
                if ($replace)
                {
                    // Replace the existing translation if it's present
                    TranslationString::updateOrCreate([
                        'locale' => $locale,
                        'group' => $group,
                        'manager' => $this->manager->managerName,
                        'key' => $key,
                    ], [
                        'value' => $string,
                        'status' => TranslationString::STATUS_SAVED,
                    ]);
                    
                    continue;
                }
                
                $translationFound = TranslationString::where('locale', $locale)->where('manager',
                    $this->manager->managerName)->where('group', $group)->where('key', $key)->first();
                
                // Create translation if it does not exist
                if ($translationFound == null)
                {
                    TranslationString::forceCreate([
                        'locale' => $locale,
                        'group' => $group,
                        'manager' => $this->manager->managerName,
                        'key' => $key,
                        'value' => $string,
                        'status' => TranslationString::STATUS_SAVED,
                    ]);
                }
            }
        }
    }
}