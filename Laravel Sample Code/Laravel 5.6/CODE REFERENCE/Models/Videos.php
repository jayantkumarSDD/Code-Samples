<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    
    use Vimeo;
    
    use App\Models\VideoMeta;
    
    class Videos extends Model
    {

        /**
         * The database table used by the model.
         *
         * @var string
         */
        protected $table = 'videos';

        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = ['title', 'category_id', 'description', 'status'];
        
        
        /**
         * @return \Illuminate\Database\Eloquent\Relations\HasMany
         */
        public function comments()
        {
            return $this->hasMany(Comment::class, 'video_id');
        }

        public function meta()
        {
            return $this->hasOne(VideoMeta::class, 'video_id', 'video_id')->withDefault();
        }

        public static function addUpdateVideo($vars)
        {
            $video = Videos::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
            $video->title = $vars['video_title'];
            $video->video_id = $vars['video_id'];
            $video->category_id = $vars['video_category'];
            $video->is_free_trial = isset($vars['is_free_trial']) ? $vars['is_free_trial'] : 'no';
            $video->description = $vars['video_desc'];
            $video->status = $vars['video_status'];
            $status = $video->save();
            return $status;
        }
        
        /**
         * Find Enabled Video by id
         * 
         * @param $id
         * @return mixed
         */
        public static function findEnabled($id)
        {
            $video = self::where('id', $id)->where('status', 'Enabled')->first();
            if (!$video->meta->video_meta) {
                $all_data = Vimeo::request('/videos/' . $video->video_id, array(), 'GET');
                if (!empty($all_data['body'])):
                    $vimeo_video_meta = $all_data['body'];
                    $video_meta = VideoMeta::firstOrNew(['video_id' => $video->video_id]);
                    $video_meta->created_date = $vimeo_video_meta['created_time'];
                    $video_meta->modified_date = $vimeo_video_meta['modified_time'];
                    $video_meta->video_meta = serialize($vimeo_video_meta);
                    $video_meta->save();
                endif;
            } 
            return $video; 
        }

    }
