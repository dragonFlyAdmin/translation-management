<?php
namespace DragonFly\TranslationManager\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Translation model
 *
 * @property integer        $id
 * @property string         $manager
 * @property string         $locale
 * @property string         $group
 * @property string         $key
 * @property string         $value
 * @property integer        $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TranslationString extends Model
{
    
    const STATUS_SAVED   = 0;
    const STATUS_CHANGED = 1;
    
    protected $table = 'translation_strings';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    protected $casts = [
        'status' => 'integer',
        'value' => 'array',
    ];
    
    /**
     * Load all groups that contain translations for a specific manager.
     *
     * @param string $manager
     *
     * @return Collection
     */
    public function scopeTranslatedGroups($query, $manager)
    {
        $select = ( $query->getConnection()->getDriverName() == 'mysql' ) ? 'DISTINCT `group`' : 'DISTINCT "group"';
        
        return $query->where('manager', $manager)
                    ->whereNotNull('value')
                    ->select(DB::raw($select));
    }
}
