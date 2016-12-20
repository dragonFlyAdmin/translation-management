<?php
// Entry
Route::get('/', 'WelcomeController@getIndex')->name('translations.dashboard');

// Load group's translations
Route::get('api/{manager}/groups/{group}/{timestamp?}', 'WelcomeController@getLoadGroupTranslations')->name('translations.groups');

// Actions
Route::post('api/{manager}/locale', 'ActionsController@postCreateLocale')->name('translations.locale');
Route::get('api/{manager}/clean', 'ActionsController@getClean')->name('translations.clean');
Route::get('api/{manager}/truncate', 'ActionsController@getTruncate')->name('translations.truncate');
Route::get('api/all/import', 'ImportController@getAll')->name('translations.import');

// Sync
Route::get('api/{manager}/scan', 'ImportController@getScan')->name('translations.scan');
Route::get('api/{manager}/import/append', 'ImportController@getAppend')->name('translations.import.append');
Route::get('api/{manager}/import/append/{group}', 'ImportController@getAppendGroup')->name('translations.import.append.group');
Route::get('api/{manager}/import/replace', 'ImportController@getReplace')->name('translations.import.replace');
Route::get('api/{manager}/import/replace/{group}', 'ImportController@getReplaceGroup')->name('translations.import.replace.group');
Route::get('api/{manager}/export', 'ExportController@getExport')->name('translations.export');
Route::get('api/{manager}/export/{group}', 'ExportController@getExportGroup')->name('translations.export.group');

// Translation keys
Route::delete('api/{manager}/keys/{group}/{key}', 'KeyController@deleteRemoveKey')->name('translations.keys.delete');
Route::post('api/{manager}/keys/{group}', 'KeyController@postSaveTranslation')->name('translations.keys.update');
Route::post('api/{manager}/keys/{group}/create', 'KeyController@postCreateKeys')->name('translations.keys.create');
Route::post('api/{manager}/keys/{group}/local', 'KeyController@postReplaceWithLocal')->name('translations.keys.local');