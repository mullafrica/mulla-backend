<?php

namespace App\Services\Interfaces;

interface IBulkTransferService
{
    public function createList(array $data, string $name);
    public function getLists();
    public function getListItems(string $id);
    public function deleteList(string $id);
    // public function decrementBalance(float $amount);
}
