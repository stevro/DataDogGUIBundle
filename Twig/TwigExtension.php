<?php

namespace Stev\DataDogAuditGUIBundle\Twig;

class TwigExtension extends \Twig_Extension
{
    /**
     * Separator between the items of a list whose items are themselves arrays
     * (e.g. a JSON column holding a list of objects).
     */
    const ITEM_SEPARATOR = ' | ';

    /**
     * Separator between the values of a flat array and between the "key: value"
     * pairs of a nested associative array.
     */
    const VALUE_SEPARATOR = ', ';

    const EMPTY_VALUE = '-';

    public function getFilters()
    {
        return [

        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('formatAuditData', [$this, 'formatAuditData']),
            new \Twig_SimpleFunction('formatKeylessAuditData', [$this, 'formatKeylessAuditData']),

        ];
    }

    public function getTests()
    {
        return [

        ];
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param string $timezoneString
     * @return string
     * @throws \Exception when an object can be rendered neither through __toString() nor getName()
     */
    public function formatAuditData($key, $data, $timezoneString = 'UTC')
    {
        return $key.': '.$this->formatValue($data, $timezoneString, $key);
    }

    /**
     * @param mixed $data
     * @param string $timezoneString
     * @return string
     * @throws \Exception when an object can be rendered neither through __toString() nor getName()
     */
    public function formatKeylessAuditData($data, $timezoneString = 'UTC')
    {
        return $this->formatValue($data, $timezoneString, null);
    }

    /**
     * Renders any logged value as one line of text, recursing into arrays.
     *
     * - null, '' and []            → "-"
     * - \DateTime                  → "d-m-Y H:i:s T" in the given timezone
     * - object                     → __toString(), else getName(), else exception
     * - bool                       → "DA" / "NU"
     * - flat array (no sub-arrays) → values joined by ", " (keys are not shown)
     * - array containing arrays    → every item rendered on its own, joined by " | ";
     *                                string keys are kept as "key: value" at every level
     *
     * @param mixed $data
     * @param string $timezoneString
     * @param string|null $key only used in the exception message
     * @return string
     * @throws \Exception
     */
    private function formatValue($data, $timezoneString, $key)
    {
        if ($data === null) {
            return self::EMPTY_VALUE;
        }

        if ($data instanceof \DateTime) {
            // Clone: the logged object must not be mutated by rendering it.
            $date = clone $data;
            $date->setTimezone(new \DateTimeZone($timezoneString));

            return $date->format('d-m-Y H:i:s T');
        }

        if (is_object($data)) {
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }

            if (method_exists($data, 'getName')) {
                return (string) $data->getName();
            }

            throw new \Exception(
                'Unable to render audit data'.($key !== null ? ' for key '.$key : '').' with object '.get_class($data)
            );
        }

        if (is_array($data)) {
            return $this->formatArray($data, $timezoneString, $key);
        }

        if (is_bool($data)) {
            return $data === true ? 'DA' : 'NU';
        }

        $string = (string) $data;

        return $string === '' ? self::EMPTY_VALUE : $string;
    }

    /**
     * @param array $data
     * @param string $timezoneString
     * @param string|null $key
     * @return string
     */
    private function formatArray(array $data, $timezoneString, $key)
    {
        if (count($data) === 0) {
            return self::EMPTY_VALUE;
        }

        if (!$this->containsArrays($data)) {
            $values = [];
            foreach ($data as $value) {
                $values[] = $this->formatValue($value, $timezoneString, $key);
            }

            return implode(self::VALUE_SEPARATOR, $values);
        }

        $items = [];
        foreach ($data as $itemKey => $item) {
            $rendered = $this->formatItem($item, $timezoneString, $key);
            $items[] = is_string($itemKey) ? $itemKey.': '.$rendered : $rendered;
        }

        return implode(self::ITEM_SEPARATOR, $items);
    }

    /**
     * One item of a list of arrays: string keys are kept ("key: value"), numeric keys are dropped.
     *
     * @param mixed $item
     * @param string $timezoneString
     * @param string|null $key
     * @return string
     */
    private function formatItem($item, $timezoneString, $key)
    {
        if (!is_array($item)) {
            return $this->formatValue($item, $timezoneString, $key);
        }

        if (count($item) === 0) {
            return self::EMPTY_VALUE;
        }

        $parts = [];
        foreach ($item as $itemKey => $value) {
            $rendered = is_array($value)
                ? $this->formatArray($value, $timezoneString, $key)
                : $this->formatValue($value, $timezoneString, $key);

            $parts[] = is_string($itemKey) ? $itemKey.': '.$rendered : $rendered;
        }

        return implode(self::VALUE_SEPARATOR, $parts);
    }

    /**
     * @param array $data
     * @return bool
     */
    private function containsArrays(array $data)
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                return true;
            }
        }

        return false;
    }
}
