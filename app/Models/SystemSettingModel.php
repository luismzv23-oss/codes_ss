<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemSettingModel extends Model
{
    protected $table            = 'system_settings';
    protected $primaryKey       = 'key';
    protected $keyType          = 'string';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->where('key', $key)->first();
        return $setting ? $setting['value'] : $default;
    }

    /**
     * Set or update a setting key and value
     */
    public function setSetting(string $key, $value): bool
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);
        $existing = $builder->where('key', $key)->get()->getRowArray();
        if ($existing) {
            return $builder->where('key', $key)->update(['value' => (string)$value]);
        }
        return $builder->insert(['key' => $key, 'value' => (string)$value]);
    }

    /**
     * Get all settings as a flat associative array
     */
    public function getAllSettings(): array
    {
        $settings = $this->findAll();
        $result = [];
        foreach ($settings as $s) {
            $result[$s['key']] = $s['value'];
        }
        return $result;
    }
}
