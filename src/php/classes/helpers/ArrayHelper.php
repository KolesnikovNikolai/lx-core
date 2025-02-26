<?php

namespace lx;

class ArrayHelper
{
    public static function iterableToArray(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        if ($iterable instanceof ArrayInterface) {
            return $iterable->toArray();
        }

        if (is_object($iterable) && method_exists($iterable, 'toArray')) {
            return $iterable->toArray();
        }

        $result = [];
        foreach ($iterable as $key => $item) {
            $result[$key] = $value;

        }
        return $result;
    }
    
    /**
     * @param mixed $array
     */
    public static function isEmpty($array): bool
    {
        if ($array === null || $array === '') {
            return true;
        }

        if (is_array($array)) {
            return empty($array);
        }

        if ($array instanceof ArrayInterface) {
            return $array->isEmpty();
        }
        
        if (is_object($array) && method_exists($array, 'toArray')) {
            return empty($array->toArray());
        }
        
        if (is_iterable($array)) {
            foreach ($array as $item) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    public static function merge(iterable $arr1, iterable $arr2): array
    {
        return array_merge(
            self::iterableToArray($arr1),
            self::iterableToArray($arr2)
        );
    }

	public static function map(iterable $array, string $field): array
	{
		$result = [];

		foreach ($array as $value) {
			if (!(is_iterable($value)) || !isset($value[$field])) {
			    continue;
            }
			$result[$value[$field]] = $value;
		}

		return $result;
	}

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public static function extract(string $key, iterable &$array, $default = null)
	{
		if (!isset($array[$key])) {
			return $default;
		}

		$result = $array[$key];
		unset($array[$key]);
		return $result;
	}

	public static function getColumn(iterable $array, string $columnName): array
    {
        $result = [];
        foreach ($array as $row) {
            if ((is_iterable($row)) && isset($row[$columnName])) {
                $result[] = $row[$columnName];
            }
        }
        return $result;
    }

	public static function pathExists(iterable $array, array $path): bool
	{
		$currentArray = $array;
		foreach ($path as $key) {
			if (!(is_iterable($currentArray)) || !isset($currentArray[$key])) {
				return false;
			}

			$currentArray = $currentArray[$key];
		}

		return true;
	}

	public static function createPath(iterable $array, iterable $path): iterable
	{
		$currentArray = &$array;
		foreach ($path as $key) {
			if (!(is_iterable($currentArray))) {
				return $array;
			}

			if (!isset($key, $currentArray)) {
				$currentArray[$key] = [];
			}

			$currentArray = &$currentArray[$key];
		}

		return $array;
	}

	public static function isDeepEmpty(iterable $arr): bool
	{
		$rec = function ($arr) use (&$rec) {
			if (self::isEmpty($arr)) {
				return true;
			}
			foreach ($arr as $value) {
				if (is_iterable($value)) {
					$empty = $rec($value);
					if (!$empty) {
						return false;
					}
				} else {
					return false;
				}
			}
		};

		return $rec($arr);
	}

	public static function mergeRecursiveDistinct(iterable $array1, iterable $array2, bool $rewrite = false): iterable
	{
        if (!self::isAssoc($array1) && !self::isAssoc($array2)) {
            return array_merge($array1, $array2);
        }

		$merged = $array1;
		foreach ($array2 as $key => $value) {
			if ((is_iterable($value)) && isset($merged[$key]) && (is_iterable($merged[$key]))) {
				$merged[$key] = self::mergeRecursiveDistinct($merged[$key], $value, $rewrite);
			} else {
                if (!isset($merged[$key]) || $rewrite) {
                    $merged[$key] = $value;
                }
			}
		}

		return $merged;
	}

	public static function isAssoc(iterable $array): bool
	{
		$counter = 0;
		foreach ($array as $key => $value) {
			if ($key !== $counter++) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Method recieves array of accosiated arrays with same keys
	 * Extract keys in the individual field
	 * Transfer arrays to countable with values order according to keys order
	 */
	public static function valuesStable(array $arr): array
	{
		$keys = array_keys($arr[0]);
		$rows = [];

		foreach ($arr as $row) {
			$current = [];
			foreach ($keys as $key) {
				$current[] = $row[$key];
			}
			$rows[] = $current;
		}

		return [
			'keys' => $keys,
			'rows' => $rows
		];
	}
}
