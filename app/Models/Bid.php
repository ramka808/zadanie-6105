<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Bid extends Model
{
    use HasFactory, HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'tender_id',
        'author_type',
        'author_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'status' => 'string',
        'author_type' => 'string',
        'version' => 'integer',
    ];

    /**
     * Get the author of the bid.
     */
    public function author()
    {
        return $this->belongsTo(Employee::class, 'author_id', 'username');
    }

    /**
     * Get the versions of the bid.
     */
    public function versions()
    {
        return $this->hasMany(BidVersion::class);
    }

    /**
     * Scope a query to only include bids of a given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include bids of a given author type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $authorType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfAuthorType($query, $authorType)
    {
        return $query->where('author_type', $authorType);
    }
}
