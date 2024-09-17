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

    public function getDescription(): string
    {
        return match($this) {
            SwhDepositStatus::Partial =>
                "multipart deposit is still ongoing",
            SwhDepositStatus::Deposited =>
                "deposit completed, ready for checks",
            SwhDepositStatus::Rejected =>
                "deposit failed the checks",
            SwhDepositStatus::Verified =>
                "content and metadata verified, ready for loading",
            SwhDepositStatus::Loading =>
                "loading in-progress",
            SwhDepositStatus::Done =>
                "loading completed successfully",
            SwhDepositStatus::Failed =>
                "the deposit loading has failed",
        };
    }

    public function isFinal(): bool
    {
        return match($this) {
            SwhDepositStatus::Partial =>
                false,
            SwhDepositStatus::Deposited =>
                false,
            SwhDepositStatus::Rejected =>
                true,
            SwhDepositStatus::Verified =>
                false,
            SwhDepositStatus::Loading =>
                false,
            SwhDepositStatus::Done =>
                true,
            SwhDepositStatus::Failed =>
                true,
        };
    }
}
