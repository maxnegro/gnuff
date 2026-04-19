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
            'image_url' => 'required|url',
        ];
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function save(array $options = [])
    {
        if (!$this->validate()) {
            throw new ValidationException($this->validator);
        }
        // Rimuovi la proprietà validator prima del salvataggio
        $attributes = $this->getAttributes();
        unset($attributes['validator']);
        $this->setRawAttributes($attributes);
        return parent::save($options);
    }

    public function validate()
    {
        $this->validator = \Validator::make($this->attributes, $this->rules());
        return !$this->validator->fails();
    }
}