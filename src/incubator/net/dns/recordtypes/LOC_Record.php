<?php
/**
 * A representation of a resource record of type <b>LOC</b>
 */
class LOC_Record extends DNSResourceRecord
{
    /* class variable definitions {{{ */

    // Static constants
    // Reference altitude in centimeters (see RFC 1876).
    protected $reference_alt     = 10000000;
    // Reference lat/lon (see RFC 1876).
    protected $reference_latlon  = 2147483648; // pow(2, 31);

    // Conversions to/from thousandths of a degree.
    protected $conv_sec          = 1000; // 1000 milisecs.
    protected $conv_min          = 60000; // sec * 60
    protected $conv_deg          = 3600000; // min * 60

    // Defaults (from RFC 1876, Section 3).
    protected $default_min       = 0;
    protected $default_sec       = 0;
    protected $default_size      = 1;
    protected $default_horiz_pre = 10000;
    protected $default_vert_pre  = 10;

    protected $data; // Contains packed binary data in the LOC format.
    protected $offset; // Offset to start reading the data.

    // Variables read directs from the raw data.
    protected $raw_latitude;
    protected $raw_longitude;
    protected $raw_alt;
    protected $size;
    protected $hp;
    protected $vp;

    // Variables set by parsing the raw data.
    protected $altitude;
    protected $degree_latitude;
    protected $degree_longitude;
    protected $min_latitude;
    protected $min_longitude;
    protected $sec_latitude;
    protected $sec_longitude;
    protected $ns_hem;
    protected $ew_hem;

    // Complete string representation of the data.
    protected $pretty_print_string;

    // Has the raw data been parsed yet?
    protected $parsed;

    // What version of the protocol are we using?
    protected $version;

    /* }}} */
    /**
     * class constructor - DNSResourceRecord(&$rro, $data, $offset = '')
     *
     * Usage:
     * $rr = new LOC_Record($rro, $data, $offset);
     * $rr->parse();
     *
     * @param        $rro
     * @param string $data   String to parse
     * @param int    $offset
     */
    function LOC_Record($rro, $data, $offset = 0)
    {
        // Keep all of the common fields.
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        // And keep the actual data.
        $this->data = $data;
        $this->offset = $offset;
    }

    /**
     * LOC_Record::parse()
     * Parses the $data field set in the constructor.
     */
    function parse() {
        if (isset($this->offset) && isset($this->data) && !($this->parsed)) {
            if ($this->rdlength > 0) {
                $off = $this->offset;

                $a = unpack(
                    "@$off/Cversion/Csize/Choriz_pre/Cvert_pre/Nlat/Nlong/Nalt",
                    $this->data
                );

                $this->version = $a['version'];
                $this->size = $this->precsize_ntoval($a['size']);
                $this->hp = $this->precsize_ntoval($a['horiz_pre']);
                $this->vp = $this->precsize_ntoval($a['vert_pre']);

                // If these are all 0, use the defaults.
                if (!$this->size) {
                    $this->size = $this->default_size;
                }

                if (!$this->hp) {
                    $this->hp = $this->default_horiz_pre;
                }

                if (!$this->vp) {
                    $this->vp = $this->default_vert_pre;
                }

                $this->raw_latitude = $a['lat'];
                $this->raw_longitude = $a['long'];
                $this->raw_alt = $a['alt'];
                $this->altitude = ($this->raw_alt - $this->reference_alt) / 100;

                $this->pretty_print_string =
                    $this->latlon2dms($this->raw_latitude, "NS", true) . ' ' .
                    $this->latlon2dms($this->raw_longitude, "EW", false) . ' ' .
                    $this->altitude . 'm ' .
                    $this->size . 'm ' .
                    $this->hp . 'm ' .
                    $this->vp . 'm';

                $this->parsed = true;
            }
        }
    }

    /**
     * @return string
     */
    function rdatastr()
    {
        if (!$this->parsed) {
            $this->parse_data();
        }

        if ($this->pretty_print_string) {
            return $this->pretty_print_string;
        }

        return '; no data';
    }

    /**
     * @param $packet
     * @param $offset
     *
     * @return string
     */
    function rr_rdata($packet, $offset)
    {
        if (!$this->parsed) {
            $this->parse_data();
        }

        $rdata = "";
        if (isset($this->version)) {
            $rdata .= pack("C", $this->version);
            if ($this->version == 0) {
                $rdata .= pack(
                    "C3", $this->precsize_valton($this->size),
                    $this->precsize_valton($this->hp),
                    $this->precsize_valton($this->vp)
                );
                $rdata .= pack(
                    "N3",
                    $this->raw_latitude,
                    $this->raw_longitude,
                    $this->raw_alt
                );
            } else {
                // We don't know how to handle other versions.
            }
        }
        return $rdata;
    }


    /**
     * @param $prec
     * @return int
     */
    function precsize_ntoval($prec)
    {
        $mantissa = (($prec >> 4) & 0x0f) % 10;
        $exponent = ($prec & 0x0f) % 10;
      //return $mantissa * $poweroften[$exponent];          // pewa: this code will always fail
        return 0;
    }

    /**
     * @param int $val
     * @return int
     */
    function precsize_valton($val)
    {
        $exponent = 0;
        while ($val >= 10) {
            $val /= 10;
            ++$exponent;
        }
        return (intval($val) << 4) | ($exponent & 0x0f);
    }

    /**
     * Now with added side effects, setting values for the class,
     * while returning a formatted string.
     * LOC_Record::latlon2dms($rawmsec, $hems, $is_lat) {{{
     *
     * @todo This should not change class state
     *
     * @param      $rawmsec
     * @param      $hems
     * @param bool $is_lat
     */
    function latlon2dms($rawmsec, $hems, $is_lat = false)
    {
        // Adjust for hemisphere problems (E and N can have negative values,
        // which need to be corrected for).
        $flipped = false;
        if ($rawmsec < 0) {
            $rawmsec = -1 * $rawmsec;
            $flipped = true;
        }

        $abs = abs($rawmsec - $this->reference_latlon);
        $deg = intval($abs / $this->conv_deg);
        $abs  -= $deg * $this->conv_deg;
        $min  = intval($abs / $this->conv_min);
        $abs -= $min * $this->conv_min;
        $sec  = intval($abs / $this->conv_sec);
        $abs -= $sec * $this->conv_sec;
        $msec = $abs;
        $hem = substr($hems, (($rawmsec >= $this->reference_latlon) ? 0 : 1), 1);
        if ($flipped) {
            $hem = substr($hems, (($rawmsec >= $this->reference_latlon) ? 1 : 0), 1);
        }

        // Save the results.
        if ($is_lat) {
            $this->degree_latitude = $deg;
            $this->min_latitude = $min;
            $this->sec_latitude = $sec;
            $this->ns_hem = $hem;
        } else {
            $this->degree_longitude = $deg;
            $this->min_longitude = $min;
            $this->sec_longitude = $sec;
            $this->ew_hem = $hem;
        }

        return sprintf("%d %02d %02d.%03d %s", $deg, $min, $sec, $msec, $hem);
    }
}
