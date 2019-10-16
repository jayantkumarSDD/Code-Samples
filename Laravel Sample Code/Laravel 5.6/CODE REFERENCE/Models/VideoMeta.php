<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class VideoMeta extends Model
    {

        /**
         * The database table used by the model.
         *
         * @var string
         */
        protected $table = 'videos_meta_data';

        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = [
            'title',
            'video_id',
            'video_meta',
            'created_date',
            'modified_date'
        ];


        protected $appends = [
            'unserlialized',
            'files',
            'pictures',
            'image_url',
            'durations'
        ];


        /**
         * Video meta Json to Collection
         *
         * @return \Illuminate\Support\Collection
         */
        public function getUnserializedAttribute()
        {
            return collect(unserialize($this->video_meta));
        }

        public function getFilesAttribute()
        {
            return collect($this->unserialized->get('files'))->sortByDesc('width');
        }

        public function getPicturesAttribute()
        {
            return $this->unserialized->get('pictures');
        }
        
        public function getDurationsAttribute(){
           return $this->unserialized->get('duration');
        }
        

        /**
         * Get Vimeo Image Url
         *
         * @return null|string
         */
        public function getImageUrlAttribute()
        {
            return ($this->pictures) ? 'https://i.vimeocdn.com/video/' . $this->getImageId() . '_1138x645.jpg' : null;
        }

        /**
         * Get Image ID
         *
         * @return string
         */
        private function getImageId()
        {
            return array_slice(explode('/', $this->pictures['uri']), -1)[0];
        }
        
        

    }
