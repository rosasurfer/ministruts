<?php
/**
 * A representation of a resource record of type <b>SRV</b>
 *
 * @package DNSUtil
 */
class SRV_Record extends DNSResourceRecord
{
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $preference;
    protected $weight;
    protected $port;
    protected $target;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function SRV_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $a = unpack("@$offset/npreference/nweight/nport", $data);
                $offset += 6;
                $packet = new DNSPacket();

                list($target, $offset) = $packet->dn_expand($data, $offset);
                $this->preference = $a['preference'];
                $this->weight = $a['weight'];
                $this->port = $a['port'];
                $this->target = $target;
            }
        } elseif (is_array($data)) {
            $this->preference = $data['preference'];
            $this->weight = $data['weight'];
            $this->port = $data['port'];
            $this->target = $data['target'];
        } else {
            preg_match("/([0-9]+)[ \t]+([0-9]+)[ \t]+([0-9]+)[ \t]+(.+)[ \t]*$/", $data, $regs);
            $this->preference = $regs[1];
            $this->weight = $regs[2];
            $this->port = $regs[3];
            $this->target = preg_replace('/(.*)\.$/', '\\1', $regs[4]);
        }
    }

    /* }}} */
    /* SRV_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if ($this->port) {
            return intval($this->preference) . ' ' . intval($this->weight) . ' ' . intval($this->port) . ' ' . $this->target . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* SRV_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (isset($this->preference)) {
            $rdata = pack('nnn', $this->preference, $this->weight, $this->port);
            $rdata .= $packet->dn_comp($this->target, $offset + strlen($rdata));
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
?>
