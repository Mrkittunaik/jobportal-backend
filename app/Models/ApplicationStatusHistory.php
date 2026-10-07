<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';
    protected $fillable = ['application_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
