<?php
// Entry
Route::get('/', 'WelcomeController@getIndex')->name('translations.dashboard');

// Load group's translations
Route::get('api/groups/{group}/{timestamp?}', 'WelcomeController@getLoadGroupTranslations')->name('translations.groups');

// Actions
Route::post('api/locale', 'ActionsController@postCreateLocale')->name('translations.locale');
Route::get('api/clean', 'ActionsController@getClean')->name('translations.clean');
Route::get('api/truncate', 'ActionsController@getTruncate')->name('translations.truncate');

// Sync
Route::get('api/scan', 'ImportController@getScan')->name('translations.scan');
Route::get('api/import/append', 'ImportController@getAppend')->name('translations.import.append');
Route::get('api/import/append/{group}', 'ImportController@getAppendGroup')->name('translations.import.append.group');
Route::get('api/import/replace', 'ImportController@getReplace')->name('translations.import.replace');
Route::get('api/import/replace/{group}', 'ImportController@getReplaceGroup')->name('translations.import.replace.group');
Route::get('api/export', 'ExportController@getExport')->name('translations.export');
Route::get('api/export/{group}', 'ExportController@getExportGroup')->name('translations.export.group');

// Translation keys
Route::delete('api/keys/{group}/{key}', 'KeyController@deleteRemoveKey')->name('translations.keys.delete');
Route::post('api/keys/{group}', 'KeyController@postSaveTranslation')->name('translations.keys.update');
Route::post('api/keys/{group}/local', 'KeyController@postReplaceWithLocal')->name('translations.keys.local');