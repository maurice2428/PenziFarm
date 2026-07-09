<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Exited = 'exited';
    case Suspended = 'suspended';
}
