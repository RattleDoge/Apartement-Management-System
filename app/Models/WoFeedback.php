<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WoFeedback extends Model
{
    protected $fillable = ['work_order_id', 'user_id', 'rating', 'comment'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
