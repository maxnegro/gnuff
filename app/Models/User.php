<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function ownedProductLists()
    {
        return $this->hasMany(ProductList::class, 'owner_id');
    }

    public function sharedProductLists()
    {
        return $this->belongsToMany(ProductList::class, 'product_list_user');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->validate()) {
                throw new ValidationException($model->validator);
            }
        });
    }

    /**
     * Validator instance for validation errors.
     * @var \Illuminate\Contracts\Validation\Validator|null
     */
    protected $validator;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    
    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }

    public function save(array $options = [])
    {
        // Valida solo in creazione
        if (!$this->exists && !$this->validate()) {
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
