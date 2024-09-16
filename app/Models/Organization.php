<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $table = 'organization';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPES = [
        'IE',
        'LLC',
        'JSC'
    ];

    public function responsibleEmployees()
    {
        return $this->belongsToMany(Employee::class, 'organization_responsible', 'organization_id', 'user_id')
                    ->withPivot('id');
    }

    public function tenders()
    {
        return $this->hasMany(Tender::class);
    }
}
