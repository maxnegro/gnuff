<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasFactory;
use App\Enums\RatingEnum;

class Rating extends Model
{
    protected $fillable = ['user_id', 'product_id', 'rating'];

    /**
     * Validator instance for validation errors.
     * @var \Illuminate\Contracts\Validation\Validator|null
     */
    protected $validator;

    protected $casts = [
        'rating' => RatingEnum::class,
    ];

    public function rules()
    {
        return [
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'rating' => 'required|in:' . implode(',', RatingEnum::values()),
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function save(array $options = [])
    {
        if (!$this->validate()) {
            throw new \Illuminate\Validation\ValidationException($this->validator);
        }
        // Rimuovi la proprietà validator prima del salvataggio
        $attributes = $this->getAttributes();
        unset($attributes['validator']);
        $this->setRawAttributes($attributes);
        return parent::save($options);
    }

    private function validate()
    {
        $validator = \Illuminate\Support\Facades\Validator::make($this->attributes, $this->rules());
        if ($validator->fails()) {
            $this->validator = $validator;
            return false;
        }
        return true;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->validate()) {
                throw new \Illuminate\Validation\ValidationException($model->validator);
            }
        });
    }
}