<?php
/**
 * A representation of a resource record of type <b>CNAME</b>
 */
class CNAME_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $cname;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function CNAME_Record(&$rro, $data, $offset = '')
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
                list($cname, $offset) = $packet->dn_expand($data, $offset);
                $this->cname = $cname;
            }
        } elseif (is_array($data)) {
            $this->cname = $data['cname'];
        } else {
            $this->cname = preg_replace("/[ \t]+(.+)[\. \t]*$/", '\\1', $data);
        }
    }

    /* }}} */
    /* CNAME_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->cname)) {
            return $this->cname . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* CNAME_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->cname)) {
            return $packet->dn_comp($this->cname, $offset);
        }
        return null;
    }

    /* }}} */
}
