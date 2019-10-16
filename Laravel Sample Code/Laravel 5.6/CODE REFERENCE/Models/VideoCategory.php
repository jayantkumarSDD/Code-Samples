<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
Use DB;

class VideoCategory extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'video_category';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','description','status','parent'];
    
    public function videos()
    {
        return $this->hasMany('App\Models\Videos','category_id');
    }
    
    
    public function activeVideos()
    {
        return $this->hasMany('App\Models\Videos','category_id')->where('status','Enabled');
    }
    
    public function childrens() {
        return $this->hasMany('App\Models\VideoCategory','parent');
    }
    
    public function activeChildrens() {
        return $this->hasMany('App\Models\VideoCategory','parent')->where('status','Enabled');
    }
    
    public function allChildren()
    {
        return $this->childrens()->with( 'allChildren' );
    }
    
    public function allChildrenWithVideos()
    {
        return $this->activeChildrens()->with( 'allChildrenWithVideos' )->with( 'activeVideos' );
    }
    
    
    public static function addUpdateVideoCategory($vars)
    {
        $cat = VideoCategory::firstOrNew(['id'=>  isset($vars['id'])?$vars['id']:'']);
        $cat->parent = !empty($vars['parent_category'])?$vars['parent_category']:0;
        $cat->title = $vars['category_title']; 
        $cat->description = $vars['category_desc'];
        $cat->status = $vars['category_status'];
        $status = $cat->save();
        return $status;
    }
    
    public static function doSearch($keyword)
    {
        $result = VideoCategory::orWhere('id','=',$keyword)
                               ->orWhere('title','LIKE',"%$keyword%")     
                               ->orWhere('created_at','LIKE',"%$keyword%")     
                               ->orWhere('updated_at','LIKE',"%$keyword%")     
                               ->orderBy('id', 'DESC')
                               ->paginate(10);
        return $result;
    }      
    public static function getAll($limit = null)
    {
        return  VideoCategory::orderBy('id', 'DESC')->paginate($limit) ;
    }
    public static function getAllVideoWithCategory($limit = null)
    {
        $videos = DB::table('videos')
                            ->join('video_category', 'videos.category_id', '=', 'video_category.id')
                            ->select('videos.*','video_category.title as category_name')
                            ->orderBy('id', 'DESC')
                            ->paginate($limit) ;
        return   $videos;
    }
    public static function doSearchVideo($keyword)
    {
        $videos = DB::table('videos')
                            ->join('video_category', 'videos.category_id', '=', 'video_category.id')
                            ->select('videos.*','video_category.title as category_name')
                            ->orWhere('videos.title','LIKE',"%$keyword%")
                            ->orWhere('video_category.title','LIKE',"%$keyword%")     
                            ->orWhere('videos.id','=',$keyword)
                            ->orWhere('videos.video_id','=',$keyword)
                            ->orderBy('videos.id', 'DESC')
                            ->paginate(10) ;
        return   $videos;
    }
    public static function getAllVideosWithCategory()
    {
        $videos = VideoCategory::with(array( 'children', 'children.videos' ))->with('videos')->where('parent',0)->orderBy('title','ASC')->get();
        return $videos;
    }
    public static function getAllVideosByCategory($category_id = NULL)
    {
        $videos = VideoCategory::with('videos')->where('id',$category_id)->orderBy('title','ASC')->get();
        return $videos;
    }
    
    
    
    public static function getAllParents()
    {
        $categories = VideoCategory::with('allChildren')->where('parent',0)->get();
        return  $categories;
    }
    
    
    public static function getCategoriesWithVideos()
    {
        $categories_videos = VideoCategory::with('allChildrenWithVideos')
                                            ->with('activeVideos')
                                            ->where('parent',0)
                                            ->where('status','Enabled')    
                                            ->get();
        return  $categories_videos;
    }
}
