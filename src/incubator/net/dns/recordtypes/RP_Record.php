<?php
/**
 * A representation of a resource record of type <b>RP</b>
 */
class RP_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $mboxdname;
    protected $txtdname;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function RP_Record(&$rro, $data, $offset = '')
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

                list($this->mboxdname, $offset) = $packet->dn_expand($data, $offset);
                list($this->txtdname, $offset) = $packet->dn_expand($data, $offset);
            }
        } elseif (is_array($data)) {

            $this->mboxdname = $data['mboxdname'];
            $this->txtdname = $data['txtdname'];
        } else {

            preg_match("/([^ ]+)\s+([^ ]+)/", $data, $matches);

            $this->mboxdname = preg_replace('/\.$/', '', $matches[1]);
            $this->txtdname = preg_replace('/\.$/', '', $matches[2]);
        }
    }

    /* }}} */
    /* RP_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->mboxdname) > 0) {
            return $this->mboxdname . '. ' . $this->txtdname . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* RP_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->mboxdname) > 0) {

            $rdata = $packet->dn_comp($this->mboxdname, $offset);
            $rdata .= $packet->dn_comp($this->txtdname, $offset + strlen($rdata));

            return $rdata;
        }
        return null;
    }

    /* }}} */
}
?>
