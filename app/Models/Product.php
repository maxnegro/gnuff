<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['barcode', 'name', 'image_url'];

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}
