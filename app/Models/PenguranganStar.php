<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Serpo;

class PenguranganStar extends Model
{
    protected $table = 'penguranganstars';
    protected $primaryKey = 'id_penguranganstar';
    protected $guarded = [];

    public function serpo()
    {
        return $this->belongsTo(Serpo::class, 'id_serpo', 'id_serpo');
    }
}
