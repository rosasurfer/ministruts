<?php
/**
 * A representation of a resource record of type <b>NAPTR</b>
 */
class NAPTR_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $order;
    protected $preference;
    protected $flags;
    protected $services;
    protected $regex;
    protected $replacement;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function NAPTR_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $a = unpack("@$offset/norder/npreference", $data);
                $offset += 4;
                $packet = new DNSPacket();

                list($flags, $offset) = DNSPacket::label_extract($data, $offset);
                list($services, $offset) = DNSPacket::label_extract($data, $offset);
                list($regex, $offset) = DNSPacket::label_extract($data, $offset);
                list($replacement, $offset) = $packet->dn_expand($data, $offset);

                $this->order = $a['order'];
                $this->preference = $a['preference'];
                $this->flags = $flags;
                $this->services = $services;
                $this->regex = $regex;
                $this->replacement = $replacement;
            }
        } elseif (is_array($data)) {
            $this->order = $data['order'];
            $this->preference = $data['preference'];
            $this->flags = $data['flags'];
            $this->services = $data['services'];
            $this->regex = $data['regex'];
            $this->replacement = $data['replacement'];
        } else {
            $data = str_replace('\\\\', chr(1) . chr(1), $data); /* disguise escaped backslash */
            $data = str_replace('\\"', chr(2) . chr(2), $data); /* disguise \" */
            preg_match('/([0-9]+)[ \t]+([0-9]+)[ \t]+("[^"]*"|[^ \t]*)[ \t]+("[^"]*"|[^ \t]*)[ \t]+("[^"]*"|[^ \t]*)[ \t]+(.*?)[ \t]*$/', $data, $regs);
            $this->preference = $regs[1];
            $this->weight = $regs[2];
            foreach($regs as $idx => $value) {
                $value = str_replace(chr(2) . chr(2), '\\"', $value);
                $value = str_replace(chr(1) . chr(1), '\\\\', $value);
                $regs[$idx] = stripslashes($value);
            }
            $this->flags = $regs[3];
            $this->services = $regs[4];
            $this->regex = $regs[5];
            $this->replacement = $regs[6];
        }
    }

    /* }}} */
    /* NAPTR_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if ($this->rdata) {
            return intval($this->order) . ' ' . intval($this->preference) . ' "' . addslashes($this->flags) . '" "' .
                   addslashes($this->services) . '" "' . addslashes($this->regex) . '" "' . addslashes($this->replacement) . '"';
        } else return '; no data';
    }

    /* }}} */
    /* NAPTR_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if ($this->preference) {
            $rdata  = pack('nn', $this->order, $this->preference);
            $rdata .= pack('C', strlen($this->flags))    . $this->flags;
            $rdata .= pack('C', strlen($this->services)) . $this->services;
            $rdata .= pack('C', strlen($this->regex))    . $this->regex;
            $rdata .= $packet->dn_comp($this->replacement, $offset + strlen($rdata));
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
?>
