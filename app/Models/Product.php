<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['barcode', 'name', 'image_url'];

    /**
     * Validator instance for validation errors.
     * @var \Illuminate\Contracts\Validation\Validator|null
     */
    protected $validator;

    public function rules()
    {
        return [
            'barcode' => 'required|string',
            'name' => 'required|string',
            'image_url' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (str_starts_with($value, '/storage/')) {
                        return;
                    }

                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        return;
                    }

                    $fail('The '.$attribute.' field must be a valid URL or a local /storage path.');
                },
            ],
        ];
    }


    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function productLists()
    {
        return $this->belongsToMany(ProductList::class, 'product_list_product');
    }

    public function save(array $options = [])
    {
        if (!$this->validate()) {
            throw new ValidationException($this->validator);
        }
        return parent::save($options);
    }

    public function validate()
    {
        $this->validator = \Validator::make($this->attributes, $this->rules());
        return !$this->validator->fails();
    }
}