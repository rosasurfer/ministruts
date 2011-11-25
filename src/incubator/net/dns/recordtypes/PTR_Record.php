<?php
/**
 * A representation of a resource record of type <b>PTR</b>
 *
 * @package DNSUtil
 */
class PTR_Record extends DNSResourceRecord
{
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $ptrdname;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function PTR_Record(&$rro, $data, $offset = '')
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

                list($ptrdname, $offset) = $packet->dn_expand($data, $offset);
                $this->ptrdname = $ptrdname;
            }
        } elseif (is_array($data)) {
            $this->ptrdname = $data['ptrdname'];
        } else {
            $this->ptrdname = preg_replace("/[ \t]+(.+)[ \t]*$/", '\\1', $data);
        }
    }

    /* }}} */
    /* PTR_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->ptrdname)) {
            return $this->ptrdname . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* PTR_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->ptrdname)) {
            return $packet->dn_comp($this->ptrdname, $offset);
        }
        return null;
    }

    /* }}} */
}
?>
