<?php

namespace App\Models;

use App\Enums\RatingEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Rating extends Model
{
    protected $fillable = ['product_list_id', 'product_id', 'rating', 'author_name'];

    /**
     * Validator instance for validation errors.
     *
     * @var Validator|null
     */
    protected $validator;

    protected $casts = [
        'rating' => RatingEnum::class,
    ];

    public function rules()
    {
        return [
            'product_list_id' => 'required|integer',
            'product_id' => 'required|integer',
            'rating' => 'required|in:'.implode(',', RatingEnum::values()),
            'author_name' => 'nullable|string|max:255',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productList()
    {
        return $this->belongsTo(ProductList::class);
    }

    public function save(array $options = [])
    {
        if (! $this->validate()) {
            throw new ValidationException($this->validator);
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
            if (! $model->validate()) {
                throw new ValidationException($model->validator);
            }
        });
    }
}
