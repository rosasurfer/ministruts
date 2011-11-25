<?php
/**
 * A representation of a resource record of type <b>HINFO</b>
 */
class HINFO_Record extends DNSResourceRecord {
    /* class variable definitions {{{ */
    protected $name;
    protected $type;
    protected $class;
    protected $ttl;
    protected $rdlength;
    protected $rdata;
    protected $cpu;
    protected $os;

    /* }}} */
    /* class constructor - DNSResourceRecord(&$rro, $data, $offset = '') {{{ */
    function HINFO_Record(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                list($cpu, $offset) = DNSPacket::label_extract($data, $offset);
                list($os,  $offset) = DNSPacket::label_extract($data, $offset);

                $this->cpu = $cpu;
                $this->os  = $os;
            }
        } elseif (is_array($data)) {
            $this->cpu = $data['cpu'];
            $this->os = $data['os'];
        } else {
            $data = str_replace('\\\\', chr(1) . chr(1), $data); /* disguise escaped backslash */
            $data = str_replace('\\"', chr(2) . chr(2), $data); /* disguise \" */

            preg_match('/("[^"]*"|[^ \t]*)[ \t]+("[^"]*"|[^ \t]*)[ \t]*$/', $data, $regs);
            foreach($regs as $idx => $value) {
                $value = str_replace(chr(2) . chr(2), '\\"', $value);
                $value = str_replace(chr(1) . chr(1), '\\\\', $value);
                $regs[$idx] = stripslashes($value);
            }

            $this->cpu = $regs[1];
            $this->os = $regs[2];
        }
    }

    /* }}} */
    /* HINFO_Record::rdatastr() {{{ */
    function rdatastr()
    {
        if ($this->text) {
            return '"' . addslashes($this->cpu) . '" "' . addslashes($this->os) . '"';
        } else return '; no data';
    }

    /* }}} */
    /* HINFO_Record::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if ($this->text) {
            $rdata  = pack('C', strlen($this->cpu)) . $this->cpu;
            $rdata .= pack('C', strlen($this->os))  . $this->os;
            return $rdata;
        }
        return null;
    }

    /* }}} */
}
?>
