<?php
/**
 * Resource Record object definition
 *
 * Builds or parses resource record sections of the DNS  packet including
 * the answer, authority, and additional  sections of the packet.
 *
 * @package DNSUtil
 */
class DNSResourceRecord
{
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    /* }}} */

    /*
     * Use DNSResourceRecord::factory() instead
     *
     * @access private
     */
    /* class constructor - DNSResourceRecord($rrdata) {{{ */
    function DNSResourceRecord($rrdata)
    {
        if ($rrdata != 'getRR') { //BC check/warning remove later
            trigger_error("Please use DNSResourceRecord::factory() instead");
        }
    }

    /*
     * Returns an DNSResourceRecord object, use this instead of constructor
     *
     * @param mixed $rr_rdata Options as string, array or data
     * @return object DNSResourceRecord or Net_DNS_RR_<type>
     * @access public
     * @see DNSResourceRecord::new_from_array DNSResourceRecord::new_from_data DNSResourceRecord::new_from_string
     */
    function &factory($rrdata, $update_type = '')
    {
        if (is_string($rrdata)) {
            $rr = &DNSResourceRecord::new_from_string($rrdata, $update_type);
        } elseif (count($rrdata) == 7) {
            list($name, $rrtype, $rrclass, $ttl, $rdlength, $data, $offset) = $rrdata;
            $rr = &DNSResourceRecord::new_from_data($name, $rrtype, $rrclass, $ttl, $rdlength, $data, $offset);
        } else {
            $rr = &DNSResourceRecord::new_from_array($rrdata);
        }
        return $rr;
    }

    /* }}} */
    /* DNSResourceRecord::new_from_data($name, $ttl, $rrtype, $rrclass, $rdlength, $data, $offset) {{{ */
    function &new_from_data($name, $rrtype, $rrclass, $ttl, $rdlength, $data, $offset)
    {
        $rr = new DNSResourceRecord('getRR');
        $rr->name = $name;
        $rr->type = $rrtype;
        $rr->class = $rrclass;
        $rr->ttl = $ttl;
        $rr->rdlength = $rdlength;
        $rr->rdata = substr($data, $offset, $rdlength);
        if (class_exists('Net_DNS_RR_' . $rrtype)) {
            $scn = 'Net_DNS_RR_' . $rrtype;
            $rr = new $scn($rr, $data, $offset);
        }
        return $rr;
    }

    /* }}} */
    /* DNSResourceRecord::new_from_string($rrstring, $update_type = '') {{{ */
    function &new_from_string($rrstring, $update_type = '')
    {
        $rr = new DNSResourceRecord('getRR');
        $ttl = 0;
        $parts = preg_split('/[\s]+/', $rrstring);
        while (count($parts) > 0) {
            $s = array_shift($parts);
            if (!isset($name)) {
                $name = preg_replace('/\.+$/', '', $s);
            } else if (preg_match('/^\d+$/', $s)) {
                $ttl = $s;
            } else if (!isset($rrclass) && ! is_null(DNSUtil::classesbyname(strtoupper($s)))) {
                $rrclass = strtoupper($s);
                $rdata = join(' ', $parts);
            } else if (! is_null(DNSUtil::typesbyname(strtoupper($s)))) {
                $rrtype = strtoupper($s);
                $rdata = join(' ', $parts);
                break;
            } else {
                break;
            }
        }

        /*
         *  Do we need to do this?
         */
        $rdata = trim(chop($rdata));

        if (! strlen($rrtype) && strlen($rrclass) && $rrclass == 'ANY') {
            $rrtype = $rrclass;
            $rrclass = 'IN';
        } else if (! isset($rrclass)) {
            $rrclass = 'IN';
        }

        if (! strlen($rrtype)) {
            $rrtype = 'ANY';
        }

        if (strlen($update_type)) {
            $update_type = strtolower($update_type);
            if ($update_type == 'yxrrset') {
                $ttl = 0;
                if (! strlen($rdata)) {
                    $rrclass = 'ANY';
                }
            } else if ($update_type == 'nxrrset') {
                $ttl = 0;
                $rrclass = 'NONE';
                $rdata = '';
            } else if ($update_type == 'yxdomain') {
                $ttl = 0;
                $rrclass = 'ANY';
                $rrtype = 'ANY';
                $rdata = '';
            } else if ($update_type == 'nxdomain') {
                $ttl = 0;
                $rrclass = 'NONE';
                $rrtype = 'ANY';
                $rdata = '';
            } else if (preg_match('/^(rr_)?add$/', $update_type)) {
                $update_type = 'add';
                if (! $ttl) {
                    $ttl = 86400;
                }
            } else if (preg_match('/^(rr_)?del(ete)?$/', $update_type)) {
                $update_type = 'del';
                $ttl = 0;
                $rrclass = $rdata ? 'NONE' : 'ANY';
            }
        }

        if (strlen($rrtype)) {
            $rr->name = $name;
            $rr->type = $rrtype;
            $rr->class = $rrclass;
            $rr->ttl = $ttl;
            $rr->rdlength = 0;
            $rr->rdata = '';

            if (class_exists('Net_DNS_RR_' . $rrtype)) {
                $scn = 'Net_DNS_RR_' . $rrtype;

                $obj = new $scn($rr, $rdata);
                return $obj;
            }

            return $rr;

        }

        return null;
    }

    /* }}} */
    /* DNSResourceRecord::new_from_array($rrarray) {{{ */
    function &new_from_array($rrarray)
    {
        $rr = new DNSResourceRecord('getRR');
        foreach ($rrarray as $k => $v) {
            $rr->{strtolower($k)} = $v;
        }

        if (! strlen($rr->name)) {
            return null;
        }
        if (! strlen($rr->type)){
            return null;
        }
        if (! $rr->ttl) {
            $rr->ttl = 0;
        }
        if (! strlen($rr->class)) {
            $rr->class = 'IN';
        }
        if (strlen($rr->rdata)) {
            $rr->rdlength = strlen($rr->rdata);
        }
        if (class_exists('Net_DNS_RR_' . $rr->type)) {
            $scn = 'Net_DNS_RR_' . $rr->type;

            $obj = new $scn($rr, !empty($rr->rdata) ? $rr->rdata : $rrarray);
            return $obj;
        }

        return $rr;
    }

    /* }}} */
    /* DNSResourceRecord::display() {{{ */
    function display()
    {
        echo $this->string() . "\n";
    }

    /* }}} */
    /* DNSResourceRecord::string() {{{ */
    function string()
    {
        return $this->name . ".\t" . (strlen($this->name) < 16 ? "\t" : '') .
                $this->ttl  . "\t"  .
                $this->class. "\t"  .
                $this->type . "\t"  .
                $this->rdatastr();

    }

    /* }}} */
    /* DNSResourceRecord::rdatastr() {{{ */
    function rdatastr()
    {
        if ($this->rdlength) {
            return '; rdlength = ' . $this->rdlength;
        }
        return '; no data';
    }

    /* }}} */
    /* DNSResourceRecord::rdata() {{{ */
    function rdata(&$packetORrdata, $offset = '')
    {
        if ($offset) {
            return $this->rr_rdata($packetORrdata, $offset);
        } else if (strlen($this->rdata)) {
            return $this->rdata;
        } else {
            return null;
        }
    }

    /* }}} */
    /* DNSResourceRecord::rr_rdata($packet, $offset) {{{ */
    function rr_rdata(&$packet, $offset)
    {
        return (strlen($this->rdata) ? $this->rdata : '');
    }
    /* }}} */
    /* DNSResourceRecord::data() {{{ */
    function data(&$packet, $offset)
    {
        $data = $packet->dn_comp($this->name, $offset);
        $data .= pack('n', DNSUtil::typesbyname(strtoupper($this->type)));
        $data .= pack('n', DNSUtil::classesbyname(strtoupper($this->class)));
        $data .= pack('N', $this->ttl);

        $offset += strlen($data) + 2;  // The 2 extra bytes are for rdlength

        $rdata = $this->rdata($packet, $offset);
        $data .= pack('n', strlen($rdata));
        $data .= $rdata;

        return $data;
    }
    /* }}} */
}
