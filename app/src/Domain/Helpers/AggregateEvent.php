<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
interface AggregateEvent
{
    public function getAggregateName(): AggregateName;

    public function getAggregateId(): AggregateId;

    public function getAggregateVersion(): AggregateVersion;

    public function serialize(): string;

    public static function deserialize(string $serialized): self;
}
