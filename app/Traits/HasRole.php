<?php
namespace App\Traits;

trait HasRole {
    public function isAdmin() {
        return $this->role->slug === 'admin';
    }

    public function isProducteur() {
        return $this->role->slug === 'producteur';
    }

    public function isEnqueteur() {
        return $this->role->slug === 'enqueteur';
    }
}