<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = ['name', 'number', 'status', 'user_id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
