<?php
/**
 * Builds or parses the QUESTION section of a DNS packet
 *
 * Builds or parses the QUESTION section of a DNS packet
 *
 * @package DNSUtil
 */
class DNSQuestion
{
    /* class variable definitions {{{ */
    protected $qname = null;
    protected $qtype = null;
    protected $qclass = null;

    /* }}} */
    /* class constructor DNSQuestion($qname, $qtype, $qclass) {{{ */
    function DNSQuestion($qname, $qtype, $qclass)
    {
        $qtype  = !is_null($qtype)  ? strtoupper($qtype)  : 'ANY';
        $qclass = !is_null($qclass) ? strtoupper($qclass) : 'ANY';

        // Check if the caller has the type and class reversed.
        // We are not that kind for unknown types.... :-)
        if ( ( is_null(DNSUtil::typesbyname($qtype)) ||
               is_null(DNSUtil::classesbyname($qtype)) )
          && !is_null(DNSUtil::classesbyname($qclass))
          && !is_null(DNSUtil::typesbyname($qclass)))
        {
            list($qtype, $qclass) = array($qclass, $qtype);
        }
        $qname = preg_replace(array('/^\.+/', '/\.+$/'), '', $qname);
        $this->qname = $qname;
        $this->qtype = $qtype;
        $this->qclass = $qclass;
    }
    /* }}} */
    /* DNSQuestion::display() {{{*/
    function display()
    {
        echo $this->string() . "\n";
    }

    /*}}}*/
    /* DNSQuestion::string() {{{*/
    function string()
    {
        return $this->qname . ".\t" . $this->qclass . "\t" . $this->qtype;
    }

    /*}}}*/
    /* DNSQuestion::data(&$packet, $offset) {{{*/
    function data($packet, $offset)
    {
        $data = $packet->dn_comp($this->qname, $offset);
        $data .= pack('n', DNSUtil::typesbyname(strtoupper($this->qtype)));
        $data .= pack('n', DNSUtil::classesbyname(strtoupper($this->qclass)));
        return $data;
    }

    /*}}}*/
}
