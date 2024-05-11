<?php

namespace App\Services;

interface IVirtualAccount
{
    public function createCustomer(array $data);
    public function createVirtualAccount(object $pt_customer, array $data);
}
