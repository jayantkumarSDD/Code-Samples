<?php

namespace App\Models;

use App\Observers\ErrorReportObserver;
use App\Traits\Model\DefaultOrderByTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ErrorReport extends Model
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
        return $this->belongsTo(Videos::class,'video_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
        parent::observe(ErrorReportObserver::class);
    }
}
