<?php

namespace App;

enum FaucetClaimStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
