<?php
namespace DragonFly\TranslationManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Translation model
 *
 * @property integer $id
 * @property integer $status
 * @property string  $locale
 * @property string  $group
 * @property string  $key
 * @property string  $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TranslationString extends Model{
    
    const STATUS_SAVED = 0;
    const STATUS_CHANGED = 1;
    
    protected $table = 'translation_strings';
    protected $guarded = array('id', 'created_at', 'updated_at');
    
    protected $casts = [
        'status' => 'integer'
    ];
}
