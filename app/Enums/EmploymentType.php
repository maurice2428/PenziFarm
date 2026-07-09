<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Casual = 'casual';
    case Internship = 'internship';
    case Probation = 'probation';
}
