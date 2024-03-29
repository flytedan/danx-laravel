<?php

namespace Flytedan\DanxLaravel\Helpers;

use Arr;
use Str;

class ArrayHelper
{
    /**
     * Flattens an associative array by recursively removing nested arrays and prefixing child keys with the parent key name
     *
     * @param $array
     * @param string $prefix
     * @return array
     */
    public static function flatMapAssoc($array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flatMapAssoc($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param $array1
     * @param $array2
     * @param bool $ignoreMissingKeys
     * @return array
     */
    public static function flattenAndDiffNestedAssoc($array1, $array2, $ignoreMissingKeys = false)
    {
        $array1 = self::flatMapAssoc($array1);
        $array2 = self::flatMapAssoc($array2);

        if ($ignoreMissingKeys) {
            $array1 = array_intersect_key($array1, $array2);
            $array2 = array_intersect_key($array2, $array1);
        }

        return array_diff_assoc($array1, $array2);
    }

    /**
     * Convert an array to a string using the keys as a label and the values as the content
     * The keys will be converted to Headline case (ie: replacing underscores/dashes/dots with spaces and capitalizing each word)
     *
     * @param $array
     * @param string $separator
     * @param string $valueSeparator
     * @return string
     */
    public static function toHeadlineString($array, $separator = "\n", $valueSeparator = ', ')
    {
        return implode($separator, Arr::map($array,
            fn($value, $key) => Str::headline(str_replace('.', ' ', $key)) . ": " . (is_array($value) ? implode(', ',
                    $value) : $value)));
    }
}
