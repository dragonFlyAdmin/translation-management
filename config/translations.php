<?php

return array(

    /**
    * Routes group config
    */
    'routes' => [
        'prefix' => 'translations-manager',
        'middleware' => 'web',
    ],
    
    /**
     * Feature toggles
     */
    'features' => [
        'delete_translations' => true,
        'create_locales' => true,
        'truncate_translations' => true
    ],

	/**
	 * Exclude specific groups from Laravel Translation Manager. 
	 * This is useful if, for example, you want to avoid editing the official Laravel language files.
	 *
	 * @type array
	 *
	 * 	array(
	 *		'pagination',
	 *		'reminders',
	 *		'validation',
	 *	)
	 */
	'exclude_groups' => array(),

);
