<?php
/**
 * A representation of a resource record of type <b>AAAA</b>
 */
class AAAA_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $address;

    /* }}} */
    /* class constructor - AAAA_Record(&$rro, $data, $offset = '') {{{ */
    function AAAA_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            $this->address = AAAA_Record::ipv6_decompress(substr($this->rdata, 0, $this->rdlength));
        } elseif (is_array($data)) {
            $this->address = $data['address'];
        } else {
            if (strlen($data)) {
                if (count($adata = explode(':', $data, 8)) >= 3) {
                    foreach($adata as $addr)
                        if (!preg_match('/^[0-9A-F]{0,4}$/i', $addr)) return;
                    $this->address = trim($data);
                }
            }
        }
    }

    /* }}} */
    /* AAAA_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->address)) {
            return $this->address;
        }
        return '; no data';
    }
    /* }}} */
    /* AAAA_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        return AAAA_Record::ipv6_compress($this->address);
    }

    /* }}} */
    /* AAAA_Record::ipv6_compress($addr) {{{ */
    function ipv6_compress($addr)
    {
        $numparts = count(explode(':', $addr));
      if ($numparts < 3 || $numparts > 8 ) {
            /* Non-sensical IPv6 address */
            return pack('n8', 0, 0, 0, 0, 0, 0, 0, 0);
        }
        if (strpos($addr, '::') !== false) {
         if (!preg_match('/^([0-9A-F]{0,4}:){0,7}(:[0-9A-F]{0,4}){0,7}$/i', $addr)) {
            return pack('n8', 0, 0, 0, 0, 0, 0, 0, 0);
         }
            /* First we have to normalize the address, turn :: into :0:0:0:0: */
            $filler = str_repeat(':0', 9 - $numparts) . ':';
            if (substr($addr, 0, 2) == '::') {
                $filler = "0$filler";
            }
            if (substr($addr, -2, 2) == '::') {
                $filler .= '0';
            }
            $addr = str_replace('::', $filler, $addr);
        } elseif (!preg_match('/^([0-9A-F]{0,4}:){7}[0-9A-F]{0,4}$/i', $addr)) {
         return pack('n8', 0, 0, 0, 0, 0, 0, 0, 0);
      }

        $aparts = explode(':', $addr);
        return pack('n8', hexdec($aparts[0]), hexdec($aparts[1]), hexdec($aparts[2]), hexdec($aparts[3]),
                          hexdec($aparts[4]), hexdec($aparts[5]), hexdec($aparts[6]), hexdec($aparts[7]));
    }
    /* }}} */

    /* AAAA_Record::ipv6_decompress($pack) {{{ */
    function ipv6_decompress($pack)
    {
        if (strlen($pack) != 16) {
            /* Must be 8 shorts long */
            return '::';
        }
        $a = unpack('n8', $pack);
        $addr = vsprintf("%x:%x:%x:%x:%x:%x:%x:%x", $a);
        /* Shorthand the first :0:0: set into a :: */
        /* TODO: Make this is a single replacement pattern */
        if (substr($addr, -4) == ':0:0') {
            return preg_replace('/((:0){2,})$/', '::', $addr);
        } elseif (substr($addr, 0, 4) == '0:0:') {
            return '0:0:'. substr($addr, 4);
        } else {
            return preg_replace('/(:(0:){2,})/', '::', $addr);
        }
    }

    /* }}} */
}
