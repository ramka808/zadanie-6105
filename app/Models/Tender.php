<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',  // Добавьте эту строку
        'name',
        'description',
        'service_type',
        'status',
        'organization_id',
        'creator_username',
        'version'
    ];

    protected $casts = [
        'id' => 'string',
        'service_type' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'creator_username', 'username');
    }

    public const SERVICE_TYPES = [
        'Construction',
        'Delivery',
        'Manufacture'
    ];

    public const STATUSES = [
        'Created',
        'Published',
        'Closed'
    ];
}
