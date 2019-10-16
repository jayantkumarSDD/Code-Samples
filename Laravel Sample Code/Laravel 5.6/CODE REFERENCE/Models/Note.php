<?php

namespace App\Models;

use App\Models\Videos;
use App\Traits\Model\DefaultOrderByTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

/**
 * Class Comment
 *
 * @package App\Models
 */
class Note extends Model
{
    use SoftDeletes, DefaultOrderByTrait;

    /**
     * @var array
     */
    protected $fillable = [
        'text',
        'video_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function video()
    {
        return $this->belongsTo(Videos::class,'video_id','video_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);    
    }
    
}
