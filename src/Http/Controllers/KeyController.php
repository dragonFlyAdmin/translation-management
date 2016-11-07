<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class KeyController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
    protected $manager;
    
    public function __construct(Router $router)
    {
        $this->manager = (new Managers())->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Update a key in the specified group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postSaveTranslation(Request $request, $manager, $group)
    {
        $key = $request->input('key');
        $locales = $request->input('locales');
        
        $this->manager->actions()->updateRecord($group, $key, $locales);
        
        return response()->json([
            'status' => 'success',
            'updates' => count($locales),
            'changed' => $this->manager->meta()->loadAmountChangedRecords(),
        ]);
    }
    
    /**
     * Replace the specified key with its local translations.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postReplaceWithLocal(Request $request, $manager, $group)
    {
        $resetTranslations = $this->manager->actions()->replaceRecordWithLocal($group, $request->input('key'));
        
        if (!$resetTranslations)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'No locales were found for this key to reset.',
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->meta()->loadAmountChangedRecords(),
            'updates' => $resetTranslations,
        ]);
    }
    
    /**
     * Remove all locales for a specific key in the database.
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRemoveKey($manager, $group, $key)
    {
        if (!$this->manager->getConfig('features')['delete_translations'])
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'This feature has been turned off.',
            ]);
        }
        
        $keyCount = $this->manager->actions()->removeRecord($group, $key);
        
        return response()->json([
            'status' => 'success',
            'deleted_entries' => ( !$keyCount ) ? 0 : $keyCount,
            'records' => $this->manager->meta()->uniqueKeys()->count(),
            'groups' => $this->manager->meta()->loadGroups(),
            'locales' => $this->manager->meta()->loadLocales(),
            'changed' => $this->manager->meta()->loadAmountChangedRecords(),
        ]);
    }
    
    /**
     * Create new keys for the specified group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCreateKeys(Request $request, $manager, $group)
    {
        if (!$this->manager->can('string.create'))
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => $this->manager->managerName . ' does not support creating new keys.',
            ]);
        }
        
        $newKeys = $request->input('keys');
        $locale = $request->input('locale');
        
        $createdRecords = $this->manager->actions()->createRecords($group, $newKeys, $locale);
        
        // Error out if none were created
        if ($createdRecords === false)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'No keys were added, they all already exist.',
            ]);
        }
        
        list( $successfulSaves, $errors, $newKeys ) = $createdRecords;
        
        // (partial) success
        return response()->json([
            'status' => 'success',
            'errors' => $errors,
            'created' => $successfulSaves,
            'keys' => $newKeys,
        ]);
    }
}