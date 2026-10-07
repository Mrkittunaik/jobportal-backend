<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = ['user_id', 'file_id', 'name', 'issuer', 'issued_date'];
    protected $casts = ['issued_date' => 'date:Y-m-d'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function file(): BelongsTo { return $this->belongsTo(File::class); }
}
