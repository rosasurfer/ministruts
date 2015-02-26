<?php
/**
 * A representation of a resource record of type <b>SOA</b>
 */
class SOA_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $mname;
    protected $rname;
    protected $serial;
    protected $refresh;
    protected $retry;
    protected $expire;
    protected $minimum;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function SOA_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $packet = new DNSPacket();

                list($mname, $offset) = $packet->dn_expand($data, $offset);
                list($rname, $offset) = $packet->dn_expand($data, $offset);

                $a = unpack("@$offset/N5soavals", $data);
                $this->mname = $mname;
                $this->rname = $rname;
                $this->serial = $a['soavals1'];
                $this->refresh = $a['soavals2'];
                $this->retry = $a['soavals3'];
                $this->expire = $a['soavals4'];
                $this->minimum = $a['soavals5'];
            }
        } elseif (is_array($data)) {
            $this->mname = $data['mname'];
            $this->rname = $data['rname'];
            $this->serial = $data['serial'];
            $this->refresh = $data['refresh'];
            $this->retry = $data['retry'];
            $this->expire = $data['expire'];
            $this->minimum = $data['minimum'];
        } else {
            if (preg_match("/([^ \t]+)[ \t]+([^ \t]+)[ \t]+([0-9]+)[ \t]+([0-9]+)[ \t]+([0-9]+)[ \t]+([0-9]+)[ \t]+([0-9]+)[ \t]*$/", $data, $regs))
            {
                $this->mname = preg_replace('/(.*)\.$/', '\\1', $regs[1]);
                $this->rname = preg_replace('/(.*)\.$/', '\\1', $regs[2]);
                $this->serial = $regs[3];
                $this->refresh = $regs[4];
                $this->retry = $regs[5];
                $this->expire = $regs[6];
                $this->minimum = $regs[7];
            }
        }
    }

    /* }}} */
    /* SOA_Record::rdatastr($pretty = 0) {{{ */
    function rdatastr($pretty = 0)
    {
        if (strlen($this->mname)) {
            if ($pretty) {
                $rdatastr  = $this->mname . '. ' . $this->rname . ". (\n";
                $rdatastr .= "\t\t\t\t\t" . $this->serial . "\t; Serial\n";
                $rdatastr .= "\t\t\t\t\t" . $this->refresh . "\t; Refresh\n";
                $rdatastr .= "\t\t\t\t\t" . $this->retry . "\t; Retry\n";
                $rdatastr .= "\t\t\t\t\t" . $this->expire . "\t; Expire\n";
                $rdatastr .= "\t\t\t\t\t" . $this->minimum . " )\t; Minimum TTL";
            } else {
                $rdatastr  = $this->mname . '. ' . $this->rname . '. ' .
                    $this->serial . ' ' .  $this->refresh . ' ' .  $this->retry . ' ' .
                    $this->expire . ' ' .  $this->minimum;
            }
            return $rdatastr;
        }
        return '; no data';
    }

    /* }}} */
    /* SOA_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->mname)) {
            $rdata = $packet->dn_comp($this->mname, $offset);
            $rdata .= $packet->dn_comp($this->rname, $offset + strlen($rdata));
            $rdata .= pack('N5', $this->serial,
                    $this->refresh,
                    $this->retry,
                    $this->expire,
                    $this->minimum);
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
