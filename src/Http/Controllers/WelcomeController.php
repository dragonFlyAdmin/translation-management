<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use Illuminate\Routing\Router;
use Illuminate\View\View;

class WelcomeController
{
    /** @var \DragonFly\TranslationManager\Manager */
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
        return view('translations-manager::welcome')
            ->with('managers', $this->loader->definitions())
            ->with('defaultLocale', config('app.locale'))
            ->with('permissions', $this->manager->config['features']);
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
        $translations = $this->manager->store()->translations($group, $timestamp);
        
        // No translations were found
        if ($translations === false)
        {
            return response()->json([
                'status' => 'empty',
                'message' => 'No (new) translations found',
            ]);
        }
        
        list($strings, $lastUpdated, $meta) = $translations;
        
        return response()->json([
            'status' => ( $timestamp === false ) ? 'new' : 'update',
            'group' => $group,
            'strings' => $strings,
            'meta' => $meta,
            'last_update' => $lastUpdated,
        ]);
    }
}