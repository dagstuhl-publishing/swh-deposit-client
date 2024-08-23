<?php

namespace Dagstuhl\SwhDepositClient;

enum SwhDepositStatus: string
{
    case Partial = "partial";
    case Deposited = "deposited";
    case Rejected = "rejected";
    case Verified = "verified";
    case Loading = "loading";
    case Done = "done";
    case Failed = "failed";
}
