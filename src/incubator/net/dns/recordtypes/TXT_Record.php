<?php
/**
 * A representation of a resource record of type <b>TXT</b>
 */
class TXT_Record extends DNSResourceRecord {

    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $text;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function TXT_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $maxoffset = $this->rdlength + $offset;
                while ($maxoffset > $offset) {
                    list($text, $offset) = DNSPacket::label_extract($data, $offset);
                    $this->text[] = $text;
                }
            }
        } elseif (is_array($data)) {
            $this->text = $data['text'];
        } else {
            $data = str_replace('\\\\', chr(1) . chr(1), $data); /* disguise escaped backslash */
            $data = str_replace('\\"', chr(2) . chr(2), $data); /* disguise \" */

            preg_match('/("[^"]*"|[^ \t]*)[ \t]*$/', $data, $regs);
            $regs[1] = str_replace(chr(2) . chr(2), '\\"', $regs[1]);
            $regs[1] = str_replace(chr(1) . chr(1), '\\\\', $regs[1]);
            $regs[1] = stripslashes($regs[1]);

            $this->text = $regs[1];
        }
    }

    /* }}} */
    /* TXT_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if ($this->text) {
             if (is_array($this->text)) {
                 $tmp = array();
                 foreach ($this->text as $t) {
                     $tmp[] = '"'.addslashes($t).'"';
                 }
                 return implode(' ',$tmp);
             } else {
                 return '"' . addslashes($this->text) . '"';
             }
        } else return '; no data';
    }

    /* }}} */
    /* TXT_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if ($this->text) {
            $rdata  = pack('C', strlen($this->text)) . $this->text;
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
?>
