<?php
/**
 * A representation of a resource record of type <b>MX</b>
 */
class MX_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $preference;
    protected $exchange;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function MX_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $a = unpack("@$offset/npreference", $data);
                $offset += 2;
                $packet = new DNSPacket();
                list($exchange, $offset) = $packet->dn_expand($data, $offset);
                $this->preference = $a['preference'];
                $this->exchange = $exchange;
            }
        } elseif (is_array($data)) {
            $this->preference = $data['preference'];
            $this->exchange = $data['exchange'];
        } else {
            preg_match("/([0-9]+)[ \t]+(.+)[ \t]*$/", $data, $regs);
            $this->preference = $regs[1];
            $this->exchange = preg_replace('/(.*)\.$/', '\\1', $regs[2]);
        }
    }

    /* }}} */
    /* MX_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (preg_match('/^[0-9]+$/', $this->preference)) {
            return $this->preference . ' ' . $this->exchange . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* MX_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (preg_match('/^[0-9]+$/', $this->preference)) {
            $rdata = pack('n', $this->preference);
            $rdata .= $packet->dn_comp($this->exchange, $offset + strlen($rdata));
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
