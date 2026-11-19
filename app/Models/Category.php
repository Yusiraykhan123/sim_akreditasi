<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_name', 'code', 'name'])]
class Category extends Model
{
    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }
}
