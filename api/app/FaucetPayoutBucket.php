<?php

namespace App;

enum FaucetPayoutBucket: string
{
    case Routine = 'routine';
    case Bonus = 'bonus';
}
