<?php

namespace lx;

/**
 * @see ArrayTrait
 */
interface ArrayInterface extends \ArrayAccess, \IteratorAggregate
{
	public function __constructArray(iterable $array);
	public function getIndex(): int;
	public function isAssoc(): bool;
	public function isEmpty(): bool;
	public function count(): int;
	public function clear(): void;
	/**
	 * @return mixed
	 */
	public function pop();
	/**
	 * @return mixed
	 */
	public function shift();
	/**
	 * @return mixed
	 */
	public function getFirst();
	/**
	 * @return mixed
	 */
	public function getLast();
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function getKeyByValue($value);
    /**
     * @param mixed $value
     */
	public function removeValue($value);
	/**
	 * @param mixed $value
	 */
	public function contains($value): bool;
	public function merge(iterable $array): void;
	public function toArray(): array;
}
