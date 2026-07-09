<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Posted = 'posted';
}
