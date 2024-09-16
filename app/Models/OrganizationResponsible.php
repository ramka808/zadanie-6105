<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationResponsible extends Model
{
    use HasFactory;

    protected $table = 'organization_responsible';

    // Указываем, что первичный ключ - это UUID
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 
        'organization_id',
    ];

    // Если вы используете uuid для отношений, укажите это
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }
}
