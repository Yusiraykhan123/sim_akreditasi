<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['element_id', 'unit_id', 'original_name', 'path', 'mime_type', 'size', 'unit', 'description'])]
class Document extends Model
{
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }

    public function unitRecord(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
