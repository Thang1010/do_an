<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class ThongBao extends DatabaseNotification
{
    protected $table = 'thong_bao';
    
    protected $primaryKey = 'ma_thong_bao';
    public $incrementing = false;
    protected $keyType = 'string';

    public function markAsRead()
    {
        if (is_null($this->da_doc_luc)) {
            $this->forceFill(['da_doc_luc' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread()
    {
        if (! is_null($this->da_doc_luc)) {
            $this->forceFill(['da_doc_luc' => null])->save();
        }
    }

    public function read()
    {
        return $this->da_doc_luc !== null;
    }

    public function unread()
    {
        return $this->da_doc_luc === null;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('da_doc_luc');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('da_doc_luc');
    }

    public function getDataAttribute()
    {
        return json_decode($this->attributes['du_lieu'], true);
    }

    public function getIdAttribute()
    {
        return $this->ma_thong_bao;
    }

    public function getReadAtAttribute()
    {
        return $this->da_doc_luc;
    }
}
