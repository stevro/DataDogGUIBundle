<?php

namespace Stev\DataDogAuditGUIBundle\Twig;

use Symfony\Component\Security\Core\User\UserInterface;

class TwigExtension extends \Twig_Extension
{


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
     * @param string|array|object $data
     * @param string $timezoneString
     * @return string
     * @throws \Exception
     */
    public function formatAuditData($key, $data, $timezoneString = 'UTC')
    {
        if ($data instanceof \DateTime) {

            $tz = new \DateTimeZone($timezoneString);
            $data->setTimezone($tz);

            return $key.': '.$data->format('d-m-Y H:i:s T');
        }

        if (is_object($data)) {

            if (method_exists($data, '__toString')) {
                return $key.': '.(string)$data;
            }

            if (method_exists($data, 'getName')) {
                return $key.': '.(string)$data->getName();
            }

            throw new \Exception('Unable to render audit data for key '.$key.' with object '.get_class($data));
        }

        if (is_array($data)) {
            return $key.': '.implode(', ', $data);
        }

        if(is_bool($data)){
            $s = $data === true ? 'DA' : 'NU';
            return $key.': '. $s;
        }

        return $key.': '.(string)$data;
    }

    /**
     *
     * @param string|array|object $data
     * @param string $timezoneString
     * @return string
     * @throws \Exception
     */
    public function formatKeylessAuditData($data, $timezoneString = 'UTC')
    {
        if ($data instanceof \DateTime) {

            $tz = new \DateTimeZone($timezoneString);
            $data->setTimezone($tz);

            return $data->format('d-m-Y H:i:s T');
        }

        if (is_object($data)) {

            if (method_exists($data, '__toString')) {
                return (string)$data;
            }

            if (method_exists($data, 'getName')) {
                return (string)$data->getName();
            }

            throw new \Exception('Unable to render audit data for key '.$key.' with object '.get_class($data));
        }

        if (is_array($data)) {
            return implode(', ', $data);
        }

        if(is_bool($data)){
            $s = $data === true ? 'DA' : 'NU';
            return $s;
        }

        return (string)$data ? (string)$data : '-';
    }


}
