<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employee';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
    ];

    protected $casts = [
        'id' => 'string',
        'username' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_responsible', 'user_id', 'organization_id');
    }

    public function createdTenders()
    {
        return $this->hasMany(Tender::class, 'creator_username', 'username');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function responsibleForOrganizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_responsible', 'user_id', 'organization_id')
                    ->withPivot('id');
    }
}
