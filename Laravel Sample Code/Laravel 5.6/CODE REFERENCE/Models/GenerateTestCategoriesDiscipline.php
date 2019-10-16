<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenerateTestCategoriesDiscipline extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'generate_test_categories_discipline';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['exam_id', 'categories', 'discipline','no_of_questions'];

    

}
