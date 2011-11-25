<?php
/**
 * A representation of a resource record of type <b>NS</b>
 *
 * @package DNSUtil
 */
class NS_Record extends DNSResourceRecord
{
    /* class variable defintiions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $nsdname;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function NS_Record(&$rro, $data, $offset = '')
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
                list($nsdname, $offset) = $packet->dn_expand($data, $offset);
                $this->nsdname = $nsdname;
            }
        } elseif (is_array($data)) {
            $this->nsdname = $data['nsdname'];
        } else {
            $this->nsdname = preg_replace("/[ \t]+(.+)[ \t]*$/", '\\1', $data);
        }
    }

    /* }}} */
    /* NS_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->nsdname)) {
            return $this->nsdname . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* NS_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->nsdname)) {
            return $packet->dn_comp($this->nsdname, $offset);
        }
        return null;
    }

    /* }}} */
}
?>
