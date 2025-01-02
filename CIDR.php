<?php

/**
 * CIDR class helps validate an ip address against a CIDR ip range.
 *
 * This class has not been optimized, but works comparing an ip address
 * with a wild card address or a CIDR ip range.
 *
 * @package     CIDR
 * @version     1.0
 * @author      Frank Forte
 * @copyright   Copyright (c) 2015 Frank Forte
 * @license     BSD-3 License https://opensource.org/licenses/BSD-3-Clause
 */
class CIDR
{
    /**
     * compare an IPv4 or IPv6 address with a CIDR address or range
     *
     * @param  string  $address a valid IPv6 address
     * @param  string  $subnet a valid IPv6 subnet[/mask]
     * @return boolean	whether $address is within the ip range made up  of the subnet and mask
     */
    public static function match($ip, $cidr)
    {

        // make sure we compare ip addresses as case insensitive
        $ip = strtolower($ip);
        $cidr = strtolower($cidr);

        // comparing exact ip?
        if ($ip == $cidr) {
            return true;
        }


        if (strpos($cidr, '/') !== false) {
            list($subnet, $mask) = explode('/', $cidr);
        } else {
            $subnet = $cidr;
            $mask = 0;
        }

        // validate ips and shorten them
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

            $ipVersion = 'v4';
            // shorten ip
            $ip = preg_replace('/^(.*\.|.*:)?0+([1-9])/', '$1$2', $ip);

        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

            $ipVersion = 'v6';
            // shorten ip
            $ip = self::IPv6_compress($ip);

        } else {

            return false; // invalid ip
        }

        // shorten cidr and subnet
        if (strpos($subnet, ':') === false) {
            $subnet = preg_replace('/^(.*\.|.*:)?0+([1-9])/', '$1$2', $subnet);
        } else {
            $pos = strpos($subnet, '*');
            if ($pos !== false) {
                $subnet = substr($subnet, 0, $pos);
                $i = 0;
                $subnet = explode(':', $subnet);
                $size = $j = sizeof($subnet);
                while ($j < 8) {
                    $subnet[] = '0';
                    $j++;
                    $i++;
                }
                $subnet = implode(':', $subnet);
                if (substr($subnet, -1) == ':') {
                    $subnet .= '0';
                }
            }

            $subnet = self::IPv6_compress($subnet);

            if ($pos !== false) {
                $subnet = explode(':', $subnet);
                $j = sizeof($subnet);
                while ($j > $size) {
                    array_pop($subnet);
                    $j--;
                }
                $subnet = implode(':', $subnet).'*';
            }
        }

        // shortened cidr
        $cidr = ($mask ? $subnet.'/'.$mask : $subnet);

        // if $cidr is ipv6, convert $ip to ipv6 for easier comparison
        if (strpos($subnet, ':') !== false && $ipVersion == 'v4') {

            $v6bits = array('0000','0000','0000','0000','0000','0000',$ip);

            $ip4parts = explode('.', $v6bits[count($v6bits) - 1]);
            $ip6trans = sprintf("%02x%02x:%02x%02x", $ip4parts[0], $ip4parts[1], $ip4parts[2], $ip4parts[3]);
            $v6bits[count($v6bits) - 1] = $ip6trans;

            $ip = implode(':', $v6bits);
            // shorten ip
            $ip = self::IPv6_compress($ip);

            $ipVersion = 'v6';
        }

        if ($ip == $cidr) {
            return true;
        }

        // wildcard matching (easier since we already shortened or "canonicalized" ip and cidr above)
        $pos = strpos($cidr, '*');
        if ($pos !== false) {
            if (substr($ip, 0, $pos) == substr($cidr, 0, $pos)) {
                return true;
            } else {
                return false;
            }
        }

        switch ($ipVersion) {
            case 'v4':
                return self::IPv4Match($ip, $subnet, $mask);
                break;
            case 'v6':
                return self::IPv6Match($ip, $subnet, $mask);
                break;
        }
    }



    /**
     * Check IPv6 address is within an IP range
     *
     * @param  string  $address a valid IPv6 address
     * @param  string  $subnet a valid IPv6 subnet
     * @param  string  $mask a valid IPv6 subnet mask
     * @return boolean	whether $address is within the ip range made up  of the subnet and mask
     */
    private static function IPv6Match($ip, $subnet, $mask)
    {
        $subnet = inet_pton($subnet);
        $ip = inet_pton($ip);

        // thanks to MW on http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
        $binMask = str_repeat("f", $mask / 4);
        switch ($mask % 4) {
            case 0:
                break;
            case 1:
                $binMask .= "8";
                break;
            case 2:
                $binMask .= "c";
                break;
            case 3:
                $binMask .= "e";
                break;
        }
        $binMask = str_pad($binMask, 32, '0');
        $binMask = pack("H*", $binMask);


        return ($ip & $binMask) == $subnet;
    }

    /**
     * Check IPv4 address is within an IP range
     *
     * @param  string  $address a valid IPv4 address
     * @param  string  $subnet a valid IPv4 subnet
     * @param  string  $mask a valid IPv4 subnet mask
     * @return boolean	whether $address is within the ip rage made up  of the subnet and mask
     */
    private static function IPv4Match($address, $subnet, $mask)
    {
        // credit goes to Sam on http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5
        if ((ip2long($address) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
            return true;
        }

        return false;
    }

    /**
    * Compress an IPv6 Address
    *
    * @param  string  $ip a valid IPv6 address or CIDR
    * @return string  IPv6 ip address or CIDR in short form notation
    */
    public static function IPv6_compress($ip)
    {
        $bits = explode('/', $ip); // in case this is a CIDR range

        // want to expand and re-compress in case we have "::" in different spots.
        $bits[0] = self::IPv6_expand($bits[0]);

        $bits[0] = inet_ntop(inet_pton($bits[0]));
        return strtolower(implode('/', $bits));
    }

    /**
    * Expand an IPv6 Address
    *
    * @param  string  $ip a valid IPv6 address
    * @return string  IPv6 ip address in long form notation
    */
    public static function IPv6_expand($ip)
    {

        $bits = explode('/', $ip); // in case this is a CIDR range

        // add missing components
        if (strpos($bits[0], '::') !== false) {
            $part = explode('::', $bits[0]);
            $part[0] = explode(':', $part[0]);
            $part[1] = explode(':', $part[1]);
            $missing = array();
            for ($i = 0; $i < (8 - (count($part[0]) + count($part[1]))); $i++) {
                array_push($missing, '0000');
            }
            $missing = array_merge($part[0], $missing);
            $part = array_merge($missing, $part[1]);
        } else {
            $part = explode(":", $bits[0]);
        }

        // Pad components to 4 characters
        foreach ($part as &$p) {
            while (strlen($p) < 4) {
                $p = '0' . $p;
            }
        }
        unset($p);

        $bits[0] = implode(':', $part);

        // if it is the incorrect length, something went wrong.
        if (strlen($bits[0]) != 39) {
            return false;
        }
        return strtolower(implode('/', $bits));
    }
}
