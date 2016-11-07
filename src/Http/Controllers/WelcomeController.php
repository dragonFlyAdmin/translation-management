<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use Carbon\Carbon;
use DragonFly\TranslationManager\Managers;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Routing\Router;
use Illuminate\View\View;

class WelcomeController
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
     * The translation manager's UI.
     *
     * @return View
     */
    public function getIndex()
    {
        $groups = $this->manager->meta()->loadGroups();
        
        return view('translations-manager::welcome')
            ->with('manager', $this->loader->managers())
            ->with('locales', $this->manager->meta()->loadLocales())
            ->with('groups', $groups)
            ->with('defaultLocale', config('app.locale'))
            ->with('stats', $this->loadStatistics($groups))
            ->with('features', $this->manager->getConfig('features'));
    }
    
    /**
     * Load various statistics.
     *
     * @param array $groups
     *
     * @return array
     */
    protected function loadStatistics($groups)
    {
        $uniqueKeys = $this->manager->meta()->uniqueKeys();
        
        return [
            'keys' => $uniqueKeys->count(), // unique keys (don't count locales)
            'groups' => count($groups), // unique groups
            'changed' => $this->manager->meta()->loadAmountChangedRecords(),// keys that need to be saved
        ];
    }
    
    /**
     * Load a specific group's translation keys & locales.
     * Optionally you can define a timestamp, to only get updated translations.
     *
     * @param string   $group
     * @param bool|int $timestamp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoadGroupTranslations($manager, $group, $timestamp = false)
    {
        $translations = $this->manager->meta()->loadTranslations($group, $timestamp);
        
        // No translations were found
        if ($translations === false)
        {
            return response()->json([
                'status' => 'empty',
                'message' => 'No (new) translations found',
            ]);
        }
        
        list($strings, $lastUpdated) = $translations;
        
        return response()->json([
            'status' => ( $timestamp === false ) ? 'new' : 'update',
            'group' => $group,
            'strings' => $strings,
            'last_update' => $lastUpdated,
        ]);
    }
}